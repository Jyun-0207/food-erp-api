<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesOrder;
use Illuminate\Http\Request;

class StoreOrderController extends Controller
{
    public function index(Request $request)
    {
        $phone = $request->input('phone');
        $orderNumber = $request->input('orderNumber');

        if (!$phone || !$orderNumber) {
            return response()->json(['message' => '請提供電話號碼及訂單編號'], 400);
        }

        $customers = Customer::where('phone', $phone)->pluck('id');

        if ($customers->isEmpty()) {
            return response()->json([]);
        }

        $orders = SalesOrder::whereIn('customerId', $customers)
            ->where('orderNumber', $orderNumber)
            ->orderBy('createdAt', 'desc')
            ->get()
            ->makeHidden(['shippingAddress']);

        return response()->json($orders);
    }
}
