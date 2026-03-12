<?php

namespace App\Services\Workflow\Nodes;

use App\Models\WorkflowInstance;
use App\Models\WorkflowRecord;
use App\Models\WorkflowTask;
use App\Notifications\WorkflowCcNotification;

/**
 * 抄送节点
 * 
 * 将流程信息抄送给指定人员，不需要审批
 * 自动流转到下一节点
 */
class CcNode extends BaseNode
{
    public function getType(): string
    {
        return 'cc';
    }

    public function getName(): string
    {
        return '抄送节点';
    }

    public function getConfigSchema(): array
    {
        return [
            'assignee_type' => [
                'type' => 'select',
                'label' => '抄送人类型',
                'options' => [
                    'user' => '指定用户',
                    'role' => '指定角色',
                    'department' => '指定部门',
                    'form_field' => '表单字段',
                    'initiator' => '发起人',
                ],
                'required' => true,
            ],
            'assignee_config' => [
                'type' => 'object',
                'label' => '抄送人配置',
            ],
            'notify_channels' => [
                'type' => 'multiselect',
                'label' => '通知渠道',
                'options' => [
                    'database' => '站内信',
                    'mail' => '邮件',
                    'dingtalk' => '钉钉',
                ],
                'default' => ['database'],
            ],
        ];
    }

    public function execute(WorkflowInstance $instance, array $node): void
    {
        // 解析抄送人
        $assigneeConfig = $this->getNodeConfig($node, 'assignee', []);
        $assigneeIds = $this->resolveAssignees($instance, $assigneeConfig);

        if (!empty($assigneeIds)) {
            // 创建抄送记录（只读任务）
            foreach ($assigneeIds as $assigneeId) {
                WorkflowTask::create([
                    'instance_id' => $instance->id,
                    'node_id' => $node['id'],
                    'node_type' => $node['type'],
                    'node_name' => $node['name'],
                    'assignee_id' => $assigneeId,
                    'assignee_type' => 'user',
                    'status' => WorkflowTask::STATUS_PROCESSED, // 直接标记为已处理
                    'action' => 'cc',
                    'processed_at' => now(),
                ]);
            }

            // 记录抄送操作
            WorkflowRecord::createRecord(
                $instance,
                $node['id'],
                $node['name'] ?? '抄送',
                $instance->initiator_id,
                'cc',
                '抄送给 ' . count($assigneeIds) . ' 人'
            );

            // 发送通知
            $this->sendNotifications($instance, $node, $assigneeIds);
        }

        // 自动流转到下一节点
        $this->engine->moveToNext($instance, $node['id']);
    }

    /**
     * 发送抄送通知
     */
    protected function sendNotifications(WorkflowInstance $instance, array $node, array $assigneeIds): void
    {
        $channels = $this->getNodeConfig($node, 'notify_channels', ['database']);

        // 如果有通知服务，发送通知
        if (class_exists('App\Notifications\WorkflowCcNotification')) {
            $users = \App\Models\User::whereIn('id', $assigneeIds)->get();
            
            foreach ($users as $user) {
                try {
                    $notification = new WorkflowCcNotification($instance, $node);
                    
                    // 设置通知渠道
                    if (method_exists($notification, 'onlyVia')) {
                        $notification->onlyVia($channels);
                    }
                    
                    $user->notify($notification);
                } catch (\Throwable $e) {
                    // 通知失败不影响流程
                    \Log::warning('抄送通知发送失败', [
                        'instance_id' => $instance->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    public function complete(WorkflowTask $task, string $action, array $data): void
    {
        // 抄送节点不需要人工处理
    }

    public function canAutoComplete(WorkflowInstance $instance, array $node): bool
    {
        return true;
    }
}
