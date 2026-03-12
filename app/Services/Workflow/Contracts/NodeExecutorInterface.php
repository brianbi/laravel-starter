<?php

namespace App\Services\Workflow\Contracts;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;

/**
 * 节点执行器接口
 * 
 * 开发者可以实现此接口来创建自定义节点类型
 */
interface NodeExecutorInterface
{
    /**
     * 获取节点类型标识
     */
    public function getType(): string;

    /**
     * 获取节点显示名称
     */
    public function getName(): string;

    /**
     * 获取节点配置 Schema（用于前端表单生成）
     * 
     * @return array 配置项定义
     * @example
     * [
     *     'assignee_type' => [
     *         'type' => 'select',
     *         'label' => '审批人类型',
     *         'options' => ['user' => '指定用户', 'role' => '指定角色'],
     *         'required' => true,
     *     ],
     *     'timeout_hours' => [
     *         'type' => 'number',
     *         'label' => '超时时间（小时）',
     *         'default' => 24,
     *     ],
     * ]
     */
    public function getConfigSchema(): array;

    /**
     * 执行节点（进入节点时调用）
     * 
     * @param WorkflowInstance $instance 流程实例
     * @param array $node 节点配置
     */
    public function execute(WorkflowInstance $instance, array $node): void;

    /**
     * 完成任务（处理任务时调用）
     * 
     * @param WorkflowTask $task 待办任务
     * @param string $action 操作类型 (approve/reject/return/delegate/add_sign)
     * @param array $data 附加数据 (comment, form_data, target_node, target_user 等)
     */
    public function complete(WorkflowTask $task, string $action, array $data): void;

    /**
     * 判断节点是否可以自动完成
     * 
     * @param WorkflowInstance $instance 流程实例
     * @param array $node 节点配置
     * @return bool 如果返回 true，节点将自动流转到下一节点
     */
    public function canAutoComplete(WorkflowInstance $instance, array $node): bool;
}
