<?php

namespace App\Http\Requests\Admin\Form;

use Illuminate\Foundation\Http\FormRequest;

class FormDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'instance_id' => 'nullable|integer|exists:workflow_instances,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'data' => '表单数据',
            'instance_id' => '流程实例ID',
        ];
    }

    public function messages(): array
    {
        return [
            'data.required' => '表单数据不能为空',
            'data.array' => '表单数据格式不正确',
            'instance_id.exists' => '流程实例不存在',
        ];
    }
}
