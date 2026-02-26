<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:1', 'max:100'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'max:20'],
            'subject' => ['required', 'min:1', 'max:200'],
            'message' => ['required', 'min:1', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '姓名為必填欄位',
            'name.min' => '姓名至少需要 1 個字元',
            'name.max' => '姓名不能超過 100 個字元',
            'email.required' => 'Email 為必填欄位',
            'email.email' => '請輸入有效的 Email 格式',
            'phone.max' => '電話不能超過 20 個字元',
            'subject.required' => '主旨為必填欄位',
            'subject.min' => '主旨至少需要 1 個字元',
            'subject.max' => '主旨不能超過 200 個字元',
            'message.required' => '訊息為必填欄位',
            'message.min' => '訊息至少需要 1 個字元',
            'message.max' => '訊息不能超過 5000 個字元',
        ];
    }
}
