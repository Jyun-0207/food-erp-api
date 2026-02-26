<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:1', 'max:200'],
            'description' => ['nullable', 'max:1000'],
            'parentId' => ['nullable', 'string'],
            'image' => ['nullable', 'max:2000'],
            'showOnFrontend' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '類別名稱為必填欄位',
            'name.min' => '類別名稱至少需要 1 個字元',
            'name.max' => '類別名稱不能超過 200 個字元',
            'description.max' => '描述不能超過 1000 個字元',
            'image.max' => '圖片網址不能超過 2000 個字元',
        ];
    }
}
