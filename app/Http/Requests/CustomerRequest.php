<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:1', 'max:200'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'min:1', 'max:20'],
            'companyName' => ['nullable', 'max:200'],
            'taxId' => ['nullable', 'max:20'],
            'creditLimit' => ['nullable', 'numeric', 'min:0'],
            'paymentTerms' => ['nullable', 'max:100'],
            'address' => ['nullable'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '客戶名稱為必填欄位',
            'name.min' => '客戶名稱至少需要 1 個字元',
            'name.max' => '客戶名稱不能超過 200 個字元',
            'email.required' => 'Email 為必填欄位',
            'email.email' => '請輸入有效的 Email 格式',
            'phone.required' => '電話為必填欄位',
            'phone.min' => '電話至少需要 1 個字元',
            'phone.max' => '電話不能超過 20 個字元',
            'companyName.max' => '公司名稱不能超過 200 個字元',
            'taxId.max' => '統一編號不能超過 20 個字元',
            'creditLimit.numeric' => '信用額度必須為數字',
            'creditLimit.min' => '信用額度不能小於 0',
            'paymentTerms.max' => '付款條件不能超過 100 個字元',
        ];
    }
}
