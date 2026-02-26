<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:1', 'max:200'],
            'description' => ['nullable', 'max:1000'],
            'managerId' => ['nullable', 'string'],
            'managerName' => ['nullable', 'max:200'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '部門名稱為必填欄位',
            'name.min' => '部門名稱至少需要 1 個字元',
            'name.max' => '部門名稱不能超過 200 個字元',
            'description.max' => '描述不能超過 1000 個字元',
            'managerName.max' => '主管姓名不能超過 200 個字元',
        ];
    }
}
