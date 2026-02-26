<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employeeNumber' => ['required', 'min:1', 'max:50'],
            'name' => ['required', 'min:1', 'max:200'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'max:20'],
            'departmentId' => ['nullable', 'string'],
            'departmentName' => ['nullable', 'max:200'],
            'position' => ['nullable', 'max:100'],
            'hireDate' => ['nullable', 'date_format:Y-m-d'],
            'resignDate' => ['nullable', 'date'],
            'pinCode' => ['nullable', 'min:4', 'max:10'],
            'status' => ['nullable', 'in:active,on_leave,resigned'],
            'shiftTypeId' => ['nullable', 'string'],
            'shiftTypeName' => ['nullable', 'max:200'],
            'notes' => ['nullable', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'employeeNumber.required' => '員工編號為必填欄位',
            'employeeNumber.min' => '員工編號至少需要 1 個字元',
            'employeeNumber.max' => '員工編號不能超過 50 個字元',
            'name.required' => '姓名為必填欄位',
            'name.min' => '姓名至少需要 1 個字元',
            'name.max' => '姓名不能超過 200 個字元',
            'email.email' => '請輸入有效的 Email 格式',
            'phone.max' => '電話不能超過 20 個字元',
            'departmentName.max' => '部門名稱不能超過 200 個字元',
            'position.max' => '職位不能超過 100 個字元',
            'hireDate.required' => '到職日期為必填欄位',
            'hireDate.date' => '到職日期格式不正確',
            'resignDate.date' => '離職日期格式不正確',
            'pinCode.min' => 'PIN 碼至少需要 4 個字元',
            'pinCode.max' => 'PIN 碼不能超過 10 個字元',
            'status.in' => '狀態必須為 active、on_leave 或 resigned',
            'shiftTypeName.max' => '班別名稱不能超過 200 個字元',
            'notes.max' => '備註不能超過 2000 個字元',
        ];
    }
}
