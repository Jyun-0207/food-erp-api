<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'max:500'],
            'referrer' => ['nullable', 'max:2000'],
            'userAgent' => ['nullable', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.max' => '頁面路徑不能超過 500 個字元',
            'referrer.max' => '來源網址不能超過 2000 個字元',
            'userAgent.max' => '瀏覽器資訊不能超過 500 個字元',
        ];
    }
}
