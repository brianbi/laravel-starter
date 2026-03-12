<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $userId = $this->route('user');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        $rules = [
            'username' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            'email' => [
                $isUpdate ? 'sometimes' : 'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:6'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['nullable', 'in:0,1'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'position_ids' => ['nullable', 'array'],
            'position_ids.*' => ['integer', 'exists:positions,id'],
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => '请输入用户名',
            'username.unique' => '用户名已存在',
            'username.regex' => '用户名必须以字母开头，只能包含字母、数字和下划线',
            'name.required' => '请输入姓名',
            'email.required' => '请输入邮箱',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '邮箱已存在',
            'password.required' => '请输入密码',
            'password.min' => '密码至少6个字符',
            'department_id.exists' => '所选部门不存在',
        ];
    }
}
