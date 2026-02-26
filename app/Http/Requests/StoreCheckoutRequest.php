<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customerName' => ['required', 'min:1', 'max:200'],
            'customerPhone' => ['required', 'min:1', 'max:20'],
            'customerEmail' => ['required', 'email'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.productId' => ['required', 'string'],
            'items.*.productName' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unitPrice' => ['required', 'numeric', 'min:0'],
            'items.*.totalPrice' => ['required', 'numeric', 'min:0'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax' => ['required', 'numeric', 'min:0'],
            'shipping' => ['required', 'numeric', 'min:0'],
            'totalAmount' => ['required', 'numeric', 'min:0'],
            'shippingAddress' => ['nullable'],
            'paymentMethod' => ['nullable', 'string'],
            'notes' => ['nullable', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customerName.required' => '客戶姓名為必填欄位',
            'customerName.min' => '客戶姓名至少需要 1 個字元',
            'customerName.max' => '客戶姓名不能超過 200 個字元',
            'customerPhone.required' => '客戶電話為必填欄位',
            'customerPhone.min' => '客戶電話至少需要 1 個字元',
            'customerPhone.max' => '客戶電話不能超過 20 個字元',
            'customerEmail.required' => '客戶 Email 為必填欄位',
            'customerEmail.email' => '請輸入有效的 Email 格式',
            'items.required' => '訂單項目為必填欄位',
            'items.array' => '訂單項目必須為陣列',
            'items.min' => '訂單至少需要 1 個項目',
            'items.*.productId.required' => '產品 ID 為必填欄位',
            'items.*.productName.required' => '產品名稱為必填欄位',
            'items.*.quantity.required' => '數量為必填欄位',
            'items.*.quantity.integer' => '數量必須為整數',
            'items.*.quantity.min' => '數量至少為 1',
            'items.*.unitPrice.required' => '單價為必填欄位',
            'items.*.unitPrice.numeric' => '單價必須為數字',
            'items.*.unitPrice.min' => '單價不能小於 0',
            'items.*.totalPrice.required' => '小計為必填欄位',
            'items.*.totalPrice.numeric' => '小計必須為數字',
            'items.*.totalPrice.min' => '小計不能小於 0',
            'subtotal.required' => '小計為必填欄位',
            'subtotal.numeric' => '小計必須為數字',
            'subtotal.min' => '小計不能小於 0',
            'tax.required' => '稅額為必填欄位',
            'tax.numeric' => '稅額必須為數字',
            'tax.min' => '稅額不能小於 0',
            'shipping.required' => '運費為必填欄位',
            'shipping.numeric' => '運費必須為數字',
            'shipping.min' => '運費不能小於 0',
            'totalAmount.required' => '總金額為必填欄位',
            'totalAmount.numeric' => '總金額必須為數字',
            'totalAmount.min' => '總金額不能小於 0',
            'notes.max' => '備註不能超過 2000 個字元',
        ];
    }
}
