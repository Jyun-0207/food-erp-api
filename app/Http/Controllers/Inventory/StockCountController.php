<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockCount;
use Illuminate\Http\Request;

class StockCountController extends Controller
{
    public function index(Request $request)
    {
        $query = StockCount::query();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $counts = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($counts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'countNumber' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'notes' => ['nullable', 'string'],
            'createdBy' => ['nullable', 'string'],
        ]);

        $count = StockCount::create($data);

        return response()->json($count, 201);
    }

    public function show(string $id)
    {
        $count = StockCount::findOrFail($id);

        return response()->json($count);
    }

    public function update(Request $request, string $id)
    {
        $count = StockCount::findOrFail($id);

        $data = $request->validate([
            'countNumber' => ['sometimes', 'string'],
            'status' => ['nullable', 'string'],
            'items' => ['sometimes', 'array'],
            'notes' => ['nullable', 'string'],
            'completedAt' => ['nullable', 'date'],
        ]);

        $count->fill($data)->save();

        return response()->json($count);
    }

    public function destroy(string $id)
    {
        $count = StockCount::findOrFail($id);
        $count->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
