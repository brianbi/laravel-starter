<?php

namespace App\Services\Workflow\Nodes;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;

/**
 * 包容网关节点
 * 
 * 分支时：根据条件决定走哪些分支（可以同时走多个）
 * 汇聚时：等待所有已激活的分支完成后才继续
 */
class InclusiveNode extends BaseNode
{
    public function getType(): string
    {
        return 'inclusive';
    }

    public function getName(): string
    {
        return '包容网关';
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
            'conditions' => [
                'type' => 'array',
                'label' => '条件配置（分支时使用）',
                'items' => [
                    'expression' => [
                        'type' => 'string',
                        'label' => '条件表达式',
                    ],
                    'target' => [
                        'type' => 'string',
                        'label' => '目标节点ID',
                    ],
                ],
            ],
            'default_target' => [
                'type' => 'string',
                'label' => '默认目标节点',
                'description' => '当所有条件都不满足时的目标节点',
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
     * 执行分支（根据条件启动满足条件的分支）
     */
    protected function executeFork(WorkflowInstance $instance, array $node): void
    {
        $conditions = $this->getNodeConfig($node, 'conditions', []);
        $defaultTarget = $this->getNodeConfig($node, 'default_target');
        $formData = $instance->form_data ?? [];

        $activatedBranches = [];

        // 检查所有条件，满足的都执行
        foreach ($conditions as $condition) {
            $expression = $condition['expression'] ?? '';
            $target = $condition['target'] ?? null;

            if (!$target) {
                continue;
            }

            if ($expression === 'default') {
                $defaultTarget = $target;
                continue;
            }

            if ($this->evaluateExpression($expression, $formData)) {
                $activatedBranches[] = $target;
            }
        }

        // 如果没有任何分支被激活，使用默认分支
        if (empty($activatedBranches) && $defaultTarget) {
            $activatedBranches[] = $defaultTarget;
        }

        if (empty($activatedBranches)) {
            return;
        }

        // 记录激活的分支数
        $instance->update([
            'form_data' => array_merge($instance->form_data ?? [], [
                '_inclusive_branches_' . $node['id'] => count($activatedBranches),
                '_inclusive_completed_' . $node['id'] => 0,
                '_inclusive_activated_' . $node['id'] => $activatedBranches,
            ]),
        ]);

        // 执行所有激活的分支
        foreach ($activatedBranches as $targetNodeId) {
            $this->engine->executeNode($instance, $targetNodeId);
        }
    }

    /**
     * 执行汇聚（等待所有激活的分支完成）
     */
    protected function executeJoin(WorkflowInstance $instance, array $node): void
    {
        $forkNodeId = $this->getNodeConfig($node, 'fork_node_id');
        
        if (!$forkNodeId) {
            // 没有对应的 fork 节点，直接通过
            $this->engine->moveToNext($instance, $node['id']);
            return;
        }

        $completedKey = '_inclusive_completed_' . $forkNodeId;
        $branchesKey = '_inclusive_branches_' . $forkNodeId;
        $activatedKey = '_inclusive_activated_' . $forkNodeId;
        
        $completed = $instance->getFormValue($completedKey, 0) + 1;
        $total = $instance->getFormValue($branchesKey, 0);
        
        // 更新完成数
        $formData = $instance->form_data ?? [];
        $formData[$completedKey] = $completed;
        $instance->update(['form_data' => $formData]);

        // 如果所有激活的分支都完成，继续流转
        if ($completed >= $total) {
            // 清理临时数据
            unset(
                $formData[$completedKey],
                $formData[$branchesKey],
                $formData[$activatedKey]
            );
            $instance->update(['form_data' => $formData]);
            
            $this->engine->moveToNext($instance, $node['id']);
        }
    }

    /**
     * 评估条件表达式
     */
    protected function evaluateExpression(string $expression, array $data): bool
    {
        $pattern = '/^(\w+)\s*(==|!=|>|>=|<|<=)\s*(.+)$/';
        
        if (!preg_match($pattern, $expression, $matches)) {
            return false;
        }

        $field = $matches[1];
        $operator = $matches[2];
        $value = trim($matches[3], '"\'');
        $fieldValue = data_get($data, $field);

        if (is_numeric($fieldValue) && is_numeric($value)) {
            $fieldValue = floatval($fieldValue);
            $value = floatval($value);
        }

        return match ($operator) {
            '==' => $fieldValue == $value,
            '!=' => $fieldValue != $value,
            '>' => $fieldValue > $value,
            '>=' => $fieldValue >= $value,
            '<' => $fieldValue < $value,
            '<=' => $fieldValue <= $value,
            default => false,
        };
    }

    public function complete(WorkflowTask $task, string $action, array $data): void
    {
        // 包容网关不需要人工处理
    }

    public function canAutoComplete(WorkflowInstance $instance, array $node): bool
    {
        return true;
    }
}
