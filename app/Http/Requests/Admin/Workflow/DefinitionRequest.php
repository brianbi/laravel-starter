<?php

namespace App\Http\Requests\Admin\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class DefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'form_type' => 'required|string|in:builtin,model',
            'form_id' => 'nullable|integer|required_if:form_type,builtin',
            'model_class' => 'nullable|string|max:200|required_if:form_type,model',
            'graph' => 'nullable|array',
            'graph.nodes' => 'nullable|array',
            'graph.edges' => 'nullable|array',
            'status' => 'nullable|integer|in:0,1',
        ];

        // 创建时需要 code
        if ($this->isMethod('POST')) {
            $rules['code'] = 'required|string|max:50|unique:workflow_definitions,code';
        }

        // 更新时 code 可选但需唯一
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['code'] = 'nullable|string|max:50|unique:workflow_definitions,code,' . $this->route('id');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'code.required' => '流程编码不能为空',
            'code.unique' => '流程编码已存在',
            'name.required' => '流程名称不能为空',
            'form_type.required' => '表单类型不能为空',
            'form_type.in' => '表单类型必须是 builtin 或 model',
            'form_id.required_if' => '使用内置表单时表单ID不能为空',
            'model_class.required_if' => '使用业务模型时模型类不能为空',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => '流程编码',
            'name' => '流程名称',
            'description' => '流程描述',
            'form_type' => '表单类型',
            'form_id' => '表单ID',
            'model_class' => '模型类',
            'graph' => '流程图',
            'status' => '状态',
        ];
    }
}
