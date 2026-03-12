<?php

namespace App\Http\Requests\Admin\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class DelegateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'delegate_id' => 'required|integer|exists:users,id',
            'definition_id' => 'nullable|integer|exists:workflow_definitions,id',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'status' => 'nullable|integer|in:0,1',
            'remark' => 'nullable|string|max:200',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'delegate_id.required' => '代理人不能为空',
            'delegate_id.exists' => '代理人不存在',
            'definition_id.exists' => '流程定义不存在',
            'start_at.required' => '开始时间不能为空',
            'end_at.required' => '结束时间不能为空',
            'end_at.after' => '结束时间必须大于开始时间',
        ];
    }

    public function attributes(): array
    {
        return [
            'delegate_id' => '代理人',
            'definition_id' => '流程定义',
            'start_at' => '开始时间',
            'end_at' => '结束时间',
            'status' => '状态',
            'remark' => '备注',
        ];
    }
}
