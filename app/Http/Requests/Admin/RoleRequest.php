<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'data_scope' => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'remark' => ['nullable', 'string', 'max:200'],
            'menu_ids' => ['nullable', 'array'],
            'menu_ids.*' => ['integer', 'exists:menus,id'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '请输入角色名称',
            'name.unique' => '角色名称已存在',
            'name.max' => '角色名称不能超过50个字符',
            'data_scope.in' => '数据权限范围不正确',
        ];
    }
}
