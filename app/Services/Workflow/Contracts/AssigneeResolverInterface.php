<?php

namespace App\Services\Workflow\Contracts;

use App\Models\WorkflowInstance;

/**
 * 审批人解析器接口
 * 
 * 用于根据配置规则解析出具体的审批人ID列表
 */
interface AssigneeResolverInterface
{
    /**
     * 获取解析器类型标识
     */
    public function getType(): string;

    /**
     * 获取解析器显示名称
     */
    public function getName(): string;

    /**
     * 解析审批人
     * 
     * @param WorkflowInstance $instance 流程实例
     * @param array $config 审批人配置
     * @return array 用户ID数组
     * @example
     * 配置示例:
     * ['type' => 'user', 'user_ids' => [1, 2, 3]]
     * ['type' => 'role', 'role_ids' => [1, 2]]
     * ['type' => 'superior', 'level' => 1]
     * ['type' => 'form_field', 'field' => 'approver_id']
     */
    public function resolve(WorkflowInstance $instance, array $config): array;
}
