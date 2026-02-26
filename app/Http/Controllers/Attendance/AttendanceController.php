<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AttendanceController extends Controller
{
    public function verifyPin(Request $request)
    {
        $request->validate([
            'employeeId' => ['required', 'string'],
            'pin' => ['required', 'string'],
        ]);

        $employeeId = $request->input('employeeId');
        $pin = $request->input('pin');

        $employee = Employee::select(['id', 'name', 'position', 'pinCode', 'status'])
            ->find($employeeId);

        if (!$employee) {
            return response()->json(['success' => false, 'error' => '找不到員工'], 404);
        }

        if ($employee->status !== 'active') {
            return response()->json(['success' => false, 'error' => '該員工帳號已停用'], 409);
        }

        if (!$employee->pinCode) {
            return response()->json(['success' => false, 'error' => '該員工尚未設定 PIN 碼'], 409);
        }

        if (!Hash::check($pin, $employee->pinCode)) {
            return response()->json(['success' => false, 'error' => 'PIN 錯誤'], 200);
        }

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'position' => $employee->position,
            ],
        ]);
    }
}
