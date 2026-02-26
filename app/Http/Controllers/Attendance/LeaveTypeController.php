<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveType::query();

        if ($request->has('isActive')) {
            $query->where('isActive', filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN));
        }

        $types = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($types);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'isPaid' => ['nullable', 'boolean'],
            'annualQuota' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $type = LeaveType::create($data);

        return response()->json($type, 201);
    }

    public function show(string $id)
    {
        $type = LeaveType::findOrFail($id);

        return response()->json($type);
    }

    public function update(Request $request, string $id)
    {
        $type = LeaveType::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'isPaid' => ['nullable', 'boolean'],
            'annualQuota' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $type->fill($data)->save();

        return response()->json($type);
    }

    public function destroy(string $id)
    {
        $type = LeaveType::findOrFail($id);
        $type->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
