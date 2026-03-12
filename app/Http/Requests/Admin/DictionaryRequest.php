<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DictionaryRequest extends FormRequest
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
        $dictionaryId = $this->route('dictionary');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'code' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/',
                Rule::unique('dictionaries', 'code')->ignore($dictionaryId),
            ],
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
            'name.required' => '请输入字典名称',
            'name.max' => '字典名称不能超过100个字符',
            'code.required' => '请输入字典编码',
            'code.unique' => '字典编码已存在',
            'code.regex' => '字典编码必须以字母开头，只能包含字母、数字和下划线',
        ];
    }
}
