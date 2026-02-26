<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email 為必填欄位',
            'email.email' => '請輸入有效的 Email 格式',
            'password.required' => '密碼為必填欄位',
            'password.min' => '密碼至少需要 6 個字元',
        ];
    }
}
