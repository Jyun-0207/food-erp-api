<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:1', 'max:200'],
            'isActive' => ['nullable', 'boolean'],
            'requiresOnlinePayment' => ['nullable', 'boolean'],
            'type' => ['nullable', 'in:normal,cvs_pickup'],
            'cvsType' => ['nullable', 'string'],
            'accountId' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '付款方式名稱為必填欄位',
            'name.min' => '付款方式名稱至少需要 1 個字元',
            'name.max' => '付款方式名稱不能超過 200 個字元',
            'type.in' => '類型必須為 normal 或 cvs_pickup',
        ];
    }
}
