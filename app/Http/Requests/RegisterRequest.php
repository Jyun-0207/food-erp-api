<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:50'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:6', 'max:100'],
            'phone' => ['nullable', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '姓名為必填欄位',
            'name.min' => '姓名至少需要 2 個字元',
            'name.max' => '姓名不能超過 50 個字元',
            'email.required' => 'Email 為必填欄位',
            'email.email' => '請輸入有效的 Email 格式',
            'email.unique' => '此 Email 已被註冊',
            'password.required' => '密碼為必填欄位',
            'password.min' => '密碼至少需要 6 個字元',
            'password.max' => '密碼不能超過 100 個字元',
            'phone.max' => '電話不能超過 20 個字元',
        ];
    }
}
