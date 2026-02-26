<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkOrder::with(['product', 'bom']);

        if ($productId = $request->input('productId')) {
            $query->where('productId', $productId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'workOrderNumber' => ['nullable', 'string'],
            'productId' => ['required', 'string'],
            'productName' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', 'string'],
            'scheduledDate' => ['nullable', 'date'],
            'bomId' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'createdBy' => ['nullable', 'string'],
        ]);

        // Auto-generate workOrderNumber if not provided
        if (empty($data['workOrderNumber'])) {
            $dateStr = now()->format('Ymd');
            $count = DB::table('work_orders')
                ->whereBetween('createdAt', [now()->startOfDay(), now()->endOfDay()])
                ->count();
            $data['workOrderNumber'] = 'WO' . $dateStr . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        }

        $order = WorkOrder::create($data);

        return response()->json($order->load(['product', 'bom']), 201);
    }

    public function show(string $id)
    {
        $order = WorkOrder::with(['product', 'bom'])->findOrFail($id);

        return response()->json($order);
    }

    public function update(Request $request, string $id)
    {
        $order = WorkOrder::findOrFail($id);

        $data = $request->validate([
            'workOrderNumber' => ['sometimes', 'string'],
            'productId' => ['sometimes', 'string'],
            'productName' => ['sometimes', 'string'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'status' => ['nullable', 'string'],
            'scheduledDate' => ['nullable', 'date'],
            'completedDate' => ['nullable', 'date'],
            'bomId' => ['nullable', 'string'],
            'materialUsage' => ['nullable', 'array'],
            'qualityCheck' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $order->fill($data)->save();

        return response()->json($order);
    }

    public function destroy(string $id)
    {
        $order = WorkOrder::findOrFail($id);
        $order->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
