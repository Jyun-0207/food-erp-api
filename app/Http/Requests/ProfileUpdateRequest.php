<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'min:2', 'max:100'],
            'phone' => ['nullable', 'max:20'],
            'address' => ['nullable', 'max:500'],
            'avatar' => ['nullable', 'max:500000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => '姓名至少需要 2 個字元',
            'name.max' => '姓名不能超過 100 個字元',
            'phone.max' => '電話不能超過 20 個字元',
            'address.max' => '地址不能超過 500 個字元',
            'avatar.max' => '頭像資料不能超過 500000 個字元',
        ];
    }
}
