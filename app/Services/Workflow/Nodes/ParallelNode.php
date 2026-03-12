<?php

namespace App\Services\Workflow\Nodes;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;

/**
 * 并行网关节点
 * 
 * 分支时：同时执行所有出边指向的节点
 * 汇聚时：等待所有入边的分支都完成后才继续
 */
class ParallelNode extends BaseNode
{
    public function getType(): string
    {
        return 'parallel';
    }

    public function getName(): string
    {
        return '并行网关';
    }

    public function getConfigSchema(): array
    {
        return [
            'gateway_type' => [
                'type' => 'select',
                'label' => '网关类型',
                'options' => [
                    'fork' => '分支（Fork）',
                    'join' => '汇聚（Join）',
                ],
                'required' => true,
            ],
            'join_branches' => [
                'type' => 'array',
                'label' => '汇聚分支',
                'description' => '需要等待的分支节点ID列表（汇聚时使用）',
            ],
        ];
    }

    public function execute(WorkflowInstance $instance, array $node): void
    {
        $gatewayType = $this->getNodeConfig($node, 'gateway_type', 'fork');

        if ($gatewayType === 'fork') {
            $this->executeFork($instance, $node);
        } else {
            $this->executeJoin($instance, $node);
        }
    }

    /**
     * 执行分支（同时启动所有分支）
     */
    protected function executeFork(WorkflowInstance $instance, array $node): void
    {
        $edges = $instance->definition->getOutgoingEdges($node['id']);

        if (empty($edges)) {
            return;
        }

        // 记录需要等待的分支数
        $branchCount = count($edges);
        $instance->update([
            'form_data' => array_merge($instance->form_data ?? [], [
                '_parallel_branches_' . $node['id'] => $branchCount,
                '_parallel_completed_' . $node['id'] => 0,
            ]),
        ]);

        // 同时执行所有分支
        foreach ($edges as $edge) {
            $targetNodeId = $edge['target'];
            $this->engine->executeNode($instance, $targetNodeId);
        }
    }

    /**
     * 执行汇聚（等待所有分支完成）
     */
    protected function executeJoin(WorkflowInstance $instance, array $node): void
    {
        $joinBranches = $this->getNodeConfig($node, 'join_branches', []);
        
        // 如果没有配置需要等待的分支，直接通过
        if (empty($joinBranches)) {
            $this->engine->moveToNext($instance, $node['id']);
            return;
        }

        // 检查是否所有分支都已完成
        // 这里需要通过记录来判断
        $forkNodeId = $this->findForkNode($instance, $node);
        
        if ($forkNodeId) {
            $completedKey = '_parallel_completed_' . $forkNodeId;
            $branchesKey = '_parallel_branches_' . $forkNodeId;
            
            $completed = $instance->getFormValue($completedKey, 0) + 1;
            $total = $instance->getFormValue($branchesKey, 0);
            
            // 更新完成数
            $formData = $instance->form_data ?? [];
            $formData[$completedKey] = $completed;
            $instance->update(['form_data' => $formData]);

            // 如果所有分支都完成，继续流转
            if ($completed >= $total) {
                // 清理临时数据
                unset($formData[$completedKey], $formData[$branchesKey]);
                $instance->update(['form_data' => $formData]);
                
                $this->engine->moveToNext($instance, $node['id']);
            }
        } else {
            // 没有找到对应的 fork 节点，直接通过
            $this->engine->moveToNext($instance, $node['id']);
        }
    }

    /**
     * 查找对应的 Fork 节点
     */
    protected function findForkNode(WorkflowInstance $instance, array $joinNode): ?string
    {
        // 简单实现：从配置中获取
        return $this->getNodeConfig($joinNode, 'fork_node_id');
    }

    /**
     * 标记分支完成（由其他节点调用）
     */
    public function markBranchCompleted(WorkflowInstance $instance, string $forkNodeId): void
    {
        $completedKey = '_parallel_completed_' . $forkNodeId;
        $completed = $instance->getFormValue($completedKey, 0) + 1;
        
        $formData = $instance->form_data ?? [];
        $formData[$completedKey] = $completed;
        $instance->update(['form_data' => $formData]);
    }

    public function complete(WorkflowTask $task, string $action, array $data): void
    {
        // 并行网关不需要人工处理
    }

    public function canAutoComplete(WorkflowInstance $instance, array $node): bool
    {
        return true;
    }
}
