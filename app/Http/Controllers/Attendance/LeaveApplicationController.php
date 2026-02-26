<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use Illuminate\Http\Request;

class LeaveApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveApplication::with(['employee', 'leaveType']);

        if ($employeeId = $request->input('employeeId')) {
            $query->where('employeeId', $employeeId);
        }

        if ($leaveTypeId = $request->input('leaveTypeId')) {
            $query->where('leaveTypeId', $leaveTypeId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $applications = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($applications);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employeeId' => ['required', 'string'],
            'employeeName' => ['required', 'string'],
            'leaveTypeId' => ['required', 'string'],
            'leaveTypeName' => ['required', 'string'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date'],
            'days' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $application = LeaveApplication::create($data);

        return response()->json($application->load(['employee', 'leaveType']), 201);
    }

    public function show(string $id)
    {
        $application = LeaveApplication::with(['employee', 'leaveType'])->findOrFail($id);

        return response()->json($application);
    }

    public function update(Request $request, string $id)
    {
        $application = LeaveApplication::findOrFail($id);

        $data = $request->validate([
            'employeeId' => ['sometimes', 'string'],
            'employeeName' => ['sometimes', 'string'],
            'leaveTypeId' => ['sometimes', 'string'],
            'leaveTypeName' => ['sometimes', 'string'],
            'startDate' => ['sometimes', 'date'],
            'endDate' => ['sometimes', 'date'],
            'days' => ['sometimes', 'numeric', 'min:0.5'],
            'reason' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'approvedBy' => ['nullable', 'string'],
            'approvedAt' => ['nullable', 'date'],
            'rejectedReason' => ['nullable', 'string'],
        ]);

        $application->fill($data)->save();

        return response()->json($application->load(['employee', 'leaveType']));
    }

    public function destroy(string $id)
    {
        $application = LeaveApplication::findOrFail($id);
        $application->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
