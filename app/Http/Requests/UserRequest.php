<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'role' => ['required', 'in:admin,manager,staff,customer'],
            'phone' => ['nullable', 'max:20'],
            'permissions' => ['nullable', 'max:5000'],
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
            'email.unique' => '此 Email 已被使用',
            'password.required' => '密碼為必填欄位',
            'password.min' => '密碼至少需要 6 個字元',
            'password.max' => '密碼不能超過 100 個字元',
            'role.required' => '角色為必填欄位',
            'role.in' => '角色必須為 admin、manager、staff 或 customer',
            'phone.max' => '電話不能超過 20 個字元',
            'permissions.max' => '權限不能超過 5000 個字元',
        ];
    }
}
