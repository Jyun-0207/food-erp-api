<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
    ) {}

    public function index(Request $request)
    {
        $query = PurchaseOrder::with('supplier');

        if ($supplierId = $request->input('supplierId')) {
            $query->where('supplierId', $supplierId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($orders);
    }

    public function store(PurchaseOrderRequest $request)
    {
        $data = $request->validated();

        if (empty($data['orderNumber'])) {
            $data['orderNumber'] = $this->accountingService->generateOrderNumber('purchase_orders', 'PO');
        }

        $order = new PurchaseOrder();
        $order->fill($data)->save();

        return response()->json($order->load('supplier'), 201);
    }

    public function show(string $id)
    {
        $order = PurchaseOrder::with('supplier')->findOrFail($id);

        return response()->json($order);
    }

    public function update(PurchaseOrderRequest $request, string $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        $order->fill($request->validated())->save();

        return response()->json($order->load('supplier'));
    }

    public function destroy(string $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        $order->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
