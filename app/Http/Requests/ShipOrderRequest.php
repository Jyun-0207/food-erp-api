<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShipOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batchSelections' => ['required', 'array', 'min:1'],
            'batchSelections.*.productId' => ['required', 'string'],
            'batchSelections.*.requiresBatch' => ['nullable', 'boolean'],
            'batchSelections.*.selectedBatches' => ['nullable', 'array'],
            'batchSelections.*.selectedBatches.*.batchId' => ['required_with:batchSelections.*.selectedBatches', 'string'],
            'batchSelections.*.selectedBatches.*.quantity' => ['required_with:batchSelections.*.selectedBatches', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'batchSelections.required' => '缺少批次選擇資料',
            'batchSelections.array' => '批次選擇資料格式不正確',
            'batchSelections.min' => '至少需要一筆批次選擇',
            'batchSelections.*.productId.required' => '每筆批次選擇需包含產品 ID',
        ];
    }
}
