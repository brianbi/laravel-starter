<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DictionaryItemRequest extends FormRequest
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
            'label' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'value' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:0,1'],
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
            'label.required' => '请输入显示标签',
            'label.max' => '显示标签不能超过100个字符',
            'value.required' => '请输入字典值',
            'value.max' => '字典值不能超过100个字符',
        ];
    }
}
