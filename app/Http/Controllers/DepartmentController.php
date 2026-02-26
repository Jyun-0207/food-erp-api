<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::withCount('employees');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('isActive')) {
            $query->where('isActive', filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN));
        }

        $departments = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($departments);
    }

    public function store(DepartmentRequest $request)
    {
        $department = new Department();
        $department->fill($request->validated())->save();

        return response()->json($department, 201);
    }

    public function show(string $id)
    {
        $department = Department::with('employees')->withCount('employees')->findOrFail($id);

        return response()->json($department);
    }

    public function update(DepartmentRequest $request, string $id)
    {
        $department = Department::findOrFail($id);
        $department->fill($request->validated())->save();

        return response()->json($department);
    }

    public function destroy(string $id)
    {
        $department = Department::findOrFail($id);
        $department->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
