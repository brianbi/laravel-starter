<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
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
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'parent_id' => ['nullable', 'integer'],
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['M', 'B', 'L'])],
            'icon' => ['nullable', 'string', 'max:100'],
            'route' => ['nullable', 'string', 'max:200'],
            'component' => ['nullable', 'string', 'max:200'],
            'redirect' => ['nullable', 'string', 'max:200'],
            'permission' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:0,1'],
            'is_hidden' => ['nullable', 'in:0,1'],
            'is_cache' => ['nullable', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:200'],
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
            'name.required' => '请输入菜单名称',
            'name.max' => '菜单名称不能超过50个字符',
            'type.in' => '菜单类型不正确',
        ];
    }
}
