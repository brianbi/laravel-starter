<?php

namespace App\Http\Requests\Admin\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class InstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'definition_code' => 'required|string|exists:workflow_definitions,code',
            'form_data' => 'nullable|array',
            'business_type' => 'nullable|string|max:100',
            'business_id' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'definition_code.required' => '流程编码不能为空',
            'definition_code.exists' => '流程定义不存在',
        ];
    }

    public function attributes(): array
    {
        return [
            'definition_code' => '流程编码',
            'form_data' => '表单数据',
            'business_type' => '业务类型',
            'business_id' => '业务ID',
        ];
    }
}
