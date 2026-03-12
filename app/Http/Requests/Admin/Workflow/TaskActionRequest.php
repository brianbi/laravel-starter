<?php

namespace App\Http\Requests\Admin\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class TaskActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $action = $this->route('action') ?? $this->input('action');

        $rules = [
            'comment' => 'nullable|string|max:500',
            'form_data' => 'nullable|array',
        ];

        // 根据操作类型添加特定规则
        switch ($action) {
            case 'return':
                $rules['target_node'] = 'nullable|string|max:50';
                break;
            case 'delegate':
                $rules['target_user'] = 'required|integer|exists:users,id';
                break;
            case 'add_sign':
                $rules['target_users'] = 'required|array|min:1';
                $rules['target_users.*'] = 'integer|exists:users,id';
                $rules['sign_type'] = 'required|string|in:before,after,parallel';
                break;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'target_user.required' => '转办目标用户不能为空',
            'target_user.exists' => '转办目标用户不存在',
            'target_users.required' => '加签用户不能为空',
            'target_users.min' => '至少选择一个加签用户',
            'target_users.*.exists' => '加签用户不存在',
            'sign_type.required' => '加签类型不能为空',
            'sign_type.in' => '加签类型必须是 before、after 或 parallel',
        ];
    }

    public function attributes(): array
    {
        return [
            'comment' => '审批意见',
            'form_data' => '表单数据',
            'target_node' => '目标节点',
            'target_user' => '目标用户',
            'target_users' => '加签用户',
            'sign_type' => '加签类型',
        ];
    }
}
