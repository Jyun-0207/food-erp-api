<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'supplierId' => [$required, 'string'],
            'supplierName' => [$required, 'max:200'],
            'items' => [$required, 'array', 'min:1'],
            'items.*.productId' => ['required_with:items', 'string'],
            'items.*.productName' => ['required_with:items', 'string'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unitPrice' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unitConversionFactor' => ['nullable', 'integer', 'min:1'],
            'totalAmount' => [$required, 'numeric', 'min:0'],
            'expectedDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplierId.required' => '供應商 ID 為必填欄位',
            'supplierName.required' => '供應商名稱為必填欄位',
            'supplierName.max' => '供應商名稱不能超過 200 個字元',
            'items.required' => '採購項目為必填欄位',
            'items.array' => '採購項目必須為陣列',
            'items.min' => '採購單至少需要 1 個項目',
            'items.*.productId.required' => '產品 ID 為必填欄位',
            'items.*.productName.required' => '產品名稱為必填欄位',
            'items.*.quantity.required' => '數量為必填欄位',
            'items.*.quantity.integer' => '數量必須為整數',
            'items.*.quantity.min' => '數量至少為 1',
            'items.*.unitPrice.required' => '單價為必填欄位',
            'items.*.unitPrice.numeric' => '單價必須為數字',
            'items.*.unitPrice.min' => '單價不能小於 0',
            'totalAmount.required' => '總金額為必填欄位',
            'totalAmount.numeric' => '總金額必須為數字',
            'totalAmount.min' => '總金額不能小於 0',
            'expectedDate.date' => '預計到貨日期格式不正確',
            'notes.max' => '備註不能超過 2000 個字元',
        ];
    }
}
