<?php

namespace App\Http\Requests\Admin;

use App\Models\ExportTask;
use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $formats = implode(',', config('export.formats', ['xlsx', 'csv']));
        $scopes = implode(',', [
            ExportTask::SCOPE_ALL,
            ExportTask::SCOPE_PAGE,
            ExportTask::SCOPE_SELECTED,
        ]);

        return [
            'scope' => "nullable|string|in:{$scopes}",
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'filters' => 'nullable|array',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'fields' => 'nullable|array',
            'fields.*' => 'string',
            'format' => "nullable|string|in:{$formats}",
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'scope.in' => '导出范围必须是 all、page 或 selected',
            'ids.array' => 'ID列表必须是数组',
            'ids.*.integer' => 'ID必须是整数',
            'page.min' => '页码必须大于0',
            'per_page.min' => '每页数量必须大于0',
            'per_page.max' => '每页数量不能超过100',
            'fields.array' => '字段列表必须是数组',
            'format.in' => '导出格式必须是 xlsx 或 csv',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'scope' => '导出范围',
            'ids' => 'ID列表',
            'filters' => '筛选条件',
            'page' => '页码',
            'per_page' => '每页数量',
            'fields' => '导出字段',
            'format' => '导出格式',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults
        $this->merge([
            'scope' => $this->input('scope', ExportTask::SCOPE_ALL),
            'format' => $this->input('format', config('export.default_format', 'xlsx')),
        ]);
    }
}
