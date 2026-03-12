<?php

namespace App\Http\Requests\Admin\Form;

use App\Models\FormDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $formId = $this->route('id');

        $rules = [
            'code' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:50',
                'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/',
                Rule::unique('form_definitions', 'code')->ignore($formId),
            ],
            'name' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:100',
            ],
            'description' => 'nullable|string|max:500',
            'fields' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'array',
            ],
            'fields.*.name' => 'required|string|max:50',
            'fields.*.type' => [
                'required',
                'string',
                Rule::in(array_keys(FormDefinition::getFieldTypes())),
            ],
            'fields.*.label' => 'required|string|max:100',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.config' => 'nullable|array',
            'fields.*.rules' => 'nullable|array',
            'layout' => 'nullable|array',
            'rules' => 'nullable|array',
            'status' => 'nullable|integer|in:0,1',
        ];

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'code' => '表单编码',
            'name' => '表单名称',
            'description' => '表单描述',
            'fields' => '字段列表',
            'fields.*.name' => '字段名称',
            'fields.*.type' => '字段类型',
            'fields.*.label' => '字段标签',
            'fields.*.required' => '是否必填',
            'fields.*.config' => '字段配置',
            'fields.*.rules' => '验证规则',
            'layout' => '布局配置',
            'rules' => '表单规则',
            'status' => '状态',
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => '表单编码必须以字母开头，只能包含字母、数字和下划线',
            'code.unique' => '表单编码已存在',
            'fields.*.type.in' => '字段类型不合法',
        ];
    }
}
