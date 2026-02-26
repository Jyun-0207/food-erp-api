<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:1', 'max:200'],
            'contactPerson' => ['nullable', 'max:100'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'min:1', 'max:20'],
            'address' => ['nullable', 'max:500'],
            'taxId' => ['nullable', 'max:20'],
            'paymentTerms' => ['nullable', 'max:100'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '供應商名稱為必填欄位',
            'name.min' => '供應商名稱至少需要 1 個字元',
            'name.max' => '供應商名稱不能超過 200 個字元',
            'contactPerson.max' => '聯絡人不能超過 100 個字元',
            'email.required' => 'Email 為必填欄位',
            'email.email' => '請輸入有效的 Email 格式',
            'phone.required' => '電話為必填欄位',
            'phone.min' => '電話至少需要 1 個字元',
            'phone.max' => '電話不能超過 20 個字元',
            'address.max' => '地址不能超過 500 個字元',
            'taxId.max' => '統一編號不能超過 20 個字元',
            'paymentTerms.max' => '付款條件不能超過 100 個字元',
        ];
    }
}
