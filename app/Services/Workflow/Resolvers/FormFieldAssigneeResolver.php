<?php

namespace App\Services\Workflow\Resolvers;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\Workflow\Contracts\AssigneeResolverInterface;

/**
 * 表单字段解析器
 * 
 * 从表单数据中获取审批人
 */
class FormFieldAssigneeResolver implements AssigneeResolverInterface
{
    public function getType(): string
    {
        return 'form_field';
    }

    public function getName(): string
    {
        return '表单字段';
    }

    /**
     * 解析审批人
     * 
     * @param WorkflowInstance $instance
     * @param array $config 配置示例:
     *   ['type' => 'form_field', 'field' => 'approver_id']
     *   ['type' => 'form_field', 'field' => 'approvers', 'multiple' => true]
     * @return array
     */
    public function resolve(WorkflowInstance $instance, array $config): array
    {
        $field = $config['field'] ?? null;
        $multiple = $config['multiple'] ?? false;
        
        if (!$field) {
            return [];
        }

        // 从表单数据中获取字段值
        $value = $instance->getFormValue($field);
        
        if (empty($value)) {
            return [];
        }

        // 处理多选情况
        if ($multiple) {
            $userIds = is_array($value) ? $value : explode(',', $value);
            $userIds = array_map('intval', array_filter($userIds));
        } else {
            $userIds = [intval($value)];
        }

        // 验证用户是否存在且启用
        return User::whereIn('id', $userIds)
            ->where('status', User::STATUS_ENABLED)
            ->pluck('id')
            ->toArray();
    }
}
