<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batchEntries' => ['required', 'array', 'min:1'],
            'batchEntries.*.productId' => ['required', 'string'],
            'batchEntries.*.productName' => ['required', 'string'],
            'batchEntries.*.quantity' => ['required', 'integer', 'min:1'],
            'batchEntries.*.unitPrice' => ['nullable', 'numeric', 'min:0'],
            'batchEntries.*.requiresBatch' => ['nullable', 'boolean'],
            'batchEntries.*.batchNumber' => ['required_if:batchEntries.*.requiresBatch,true', 'nullable', 'string'],
            'batchEntries.*.manufacturingDate' => ['nullable', 'date'],
            'batchEntries.*.expirationDate' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'batchEntries.required' => '缺少收貨品項資料',
            'batchEntries.array' => '收貨品項資料格式不正確',
            'batchEntries.min' => '至少需要一筆收貨品項',
            'batchEntries.*.productId.required' => '每筆品項需包含產品 ID',
            'batchEntries.*.productName.required' => '每筆品項需包含產品名稱',
            'batchEntries.*.quantity.required' => '每筆品項需包含數量',
            'batchEntries.*.quantity.min' => '數量至少為 1',
            'batchEntries.*.batchNumber.required_if' => '需要批號追蹤的產品必須提供批號',
        ];
    }
}
