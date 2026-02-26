<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'shiftType']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employeeNumber', 'like', "%{$search}%");
            });
        }

        if ($request->has('departmentId')) {
            $query->where('departmentId', $request->input('departmentId'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $employees = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        // Strip pinCode, add hasPinCode boolean (matching original API)
        $employees->getCollection()->transform(function ($employee) {
            return $this->stripPinCode($employee);
        });

        return response()->json($employees);
    }

    public function store(EmployeeRequest $request)
    {
        $employee = new Employee();
        $data = $request->validated();
        if (!empty($data['pinCode'])) {
            $data['pinCode'] = Hash::make($data['pinCode']);
        }
        $employee->fill($data)->save();
        $employee->load(['department', 'shiftType']);

        return response()->json($this->stripPinCode($employee), 201);
    }

    public function show(string $id)
    {
        $employee = Employee::with(['department', 'shiftType'])->findOrFail($id);

        return response()->json($this->stripPinCode($employee));
    }

    public function update(EmployeeRequest $request, string $id)
    {
        $employee = Employee::findOrFail($id);
        $data = $request->validated();
        if (!empty($data['pinCode'])) {
            $data['pinCode'] = Hash::make($data['pinCode']);
        } else {
            unset($data['pinCode']);
        }
        $employee->fill($data)->save();
        $employee->load(['department', 'shiftType']);

        return response()->json($this->stripPinCode($employee));
    }

    public function destroy(string $id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return response()->json(['message' => '刪除成功']);
    }

    /**
     * Strip pinCode from employee response and add hasPinCode boolean.
     */
    private function stripPinCode(Employee $employee): array
    {
        $data = $employee->toArray();
        $data['hasPinCode'] = !empty($data['pinCode']);
        unset($data['pinCode']);
        return $data;
    }
}
