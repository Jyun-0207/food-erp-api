<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesOrderRequest;
use App\Models\SalesOrder;
use App\Models\SiteSetting;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
    ) {}

    public function index(Request $request)
    {
        $query = SalesOrder::with('customer');

        if ($customerId = $request->input('customerId')) {
            $query->where('customerId', $customerId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where('orderNumber', 'like', "%{$search}%");
        }

        $orders = $query->orderBy('createdAt', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function store(SalesOrderRequest $request)
    {
        $data = $request->validated();

        // Only generate order number if explicitly provided (e.g. Excel import)
        // Pending orders created via UI will get their number on confirmation
        if (!isset($data['numberingDate'])) {
            $data['numberingDate'] = now()->toDateString();
        }

        $order = new SalesOrder();
        $order->fill($data)->save();

        return response()->json($order->load('customer'), 201);
    }

    public function show(string $id)
    {
        $order = SalesOrder::with('customer')->findOrFail($id);

        return response()->json($order);
    }

    public function update(SalesOrderRequest $request, string $id)
    {
        $order = SalesOrder::findOrFail($id);
        $data = $request->validated();

        // Generate order number when order is first confirmed and has no number yet
        if (
            !empty($data['status']) &&
            $data['status'] === 'confirmed' &&
            empty($order->orderNumber)
        ) {
            $settings = SiteSetting::whereIn('key', ['salesOrderPrefix', 'salesOrderDigits'])->pluck('value', 'key');
            $prefix = $settings['salesOrderPrefix'] ?? 'SO';
            $digits = (int) ($settings['salesOrderDigits'] ?? 4);
            $numberingDate = $order->numberingDate?->toDateString() ?? now()->toDateString();
            $data['orderNumber'] = $this->accountingService->generateOrderNumber('sales_orders', $prefix, $digits, $numberingDate);
        }

        $order->fill($data)->save();

        return response()->json($order->load('customer'));
    }

    public function destroy(string $id)
    {
        $order = SalesOrder::findOrFail($id);
        $order->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
