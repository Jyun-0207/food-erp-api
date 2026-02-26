<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\ShiftType;
use Illuminate\Http\Request;

class ShiftTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ShiftType::query();

        if ($request->has('isActive')) {
            $query->where('isActive', filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN));
        }

        $shifts = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($shifts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'startTime' => ['required', 'string'],
            'endTime' => ['required', 'string'],
            'breakStartTime' => ['nullable', 'string'],
            'breakEndTime' => ['nullable', 'string'],
            'graceMinutes' => ['nullable', 'integer'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $shift = ShiftType::create($data);

        return response()->json($shift, 201);
    }

    public function show(string $id)
    {
        $shift = ShiftType::findOrFail($id);

        return response()->json($shift);
    }

    public function update(Request $request, string $id)
    {
        $shift = ShiftType::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'startTime' => ['sometimes', 'string'],
            'endTime' => ['sometimes', 'string'],
            'breakStartTime' => ['nullable', 'string'],
            'breakEndTime' => ['nullable', 'string'],
            'graceMinutes' => ['nullable', 'integer'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $shift->fill($data)->save();

        return response()->json($shift);
    }

    public function destroy(string $id)
    {
        $shift = ShiftType::findOrFail($id);
        $shift->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
