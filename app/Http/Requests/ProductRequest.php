<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:1', 'max:200'],
            'description' => ['nullable', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'costPrice' => ['required', 'numeric', 'min:0'],
            'categoryId' => ['nullable', 'string'],
            'purchaseAccountId' => ['nullable', 'string'],
            'salesAccountId' => ['nullable', 'string'],
            'sku' => ['required', 'min:1', 'max:50'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'minStock' => ['nullable', 'integer', 'min:0'],
            'unit' => ['required', 'min:1', 'max:20'],
            'images' => ['nullable', 'array'],
            'taxable' => ['nullable', 'boolean'],
            'purchasable' => ['nullable', 'boolean'],
            'supplierIds' => ['nullable', 'array'],
            'allergenIds' => ['nullable', 'array'],
            'categoryIds' => ['nullable', 'array'],
            'requiresBatch' => ['nullable', 'boolean'],
            'shelfLife' => ['nullable', 'integer', 'min:0'],
            'shelfLifeUnit' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '產品名稱為必填欄位',
            'name.min' => '產品名稱至少需要 1 個字元',
            'name.max' => '產品名稱不能超過 200 個字元',
            'description.max' => '描述不能超過 5000 個字元',
            'price.required' => '售價為必填欄位',
            'price.numeric' => '售價必須為數字',
            'price.min' => '售價不能小於 0',
            'costPrice.required' => '成本價為必填欄位',
            'costPrice.numeric' => '成本價必須為數字',
            'costPrice.min' => '成本價不能小於 0',
            'sku.required' => 'SKU 為必填欄位',
            'sku.min' => 'SKU 至少需要 1 個字元',
            'sku.max' => 'SKU 不能超過 50 個字元',
            'stock.integer' => '庫存必須為整數',
            'stock.min' => '庫存不能小於 0',
            'minStock.integer' => '最低庫存必須為整數',
            'minStock.min' => '最低庫存不能小於 0',
            'unit.required' => '單位為必填欄位',
            'unit.min' => '單位至少需要 1 個字元',
            'unit.max' => '單位不能超過 20 個字元',
            'shelfLife.integer' => '保質期必須為整數',
            'shelfLife.min' => '保質期不能小於 0',
        ];
    }
}
