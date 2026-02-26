<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckoutRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class StoreCheckoutController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
    ) {}

    public function checkout(StoreCheckoutRequest $request)
    {
        try {
            $data = $request->validated();

            $salesOrder = DB::transaction(function () use ($data) {
                // Find or create customer by phone
                $customer = Customer::where('phone', $data['customerPhone'])->first();

                if (!$customer) {
                    $customer = Customer::create([
                        'name' => $data['customerName'],
                        'email' => $data['customerEmail'] ?? '',
                        'phone' => $data['customerPhone'],
                        'isActive' => true,
                    ]);
                }

                // Server-side price verification: recalculate totals from DB prices
                $productIds = collect($data['items'])->pluck('productId');
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                $verifiedItems = [];
                $subtotal = '0';
                foreach ($data['items'] as $item) {
                    $product = $products->get($item['productId']);
                    if (!$product) {
                        throw new \Exception("產品 {$item['productName']} 不存在");
                    }
                    $unitPrice = (string) $product->price;
                    $totalPrice = bcmul($unitPrice, (string) $item['quantity'], 2);
                    $subtotal = bcadd($subtotal, $totalPrice, 2);

                    $verifiedItems[] = array_merge($item, [
                        'unitPrice' => (float) $unitPrice,
                        'totalPrice' => (float) $totalPrice,
                    ]);
                }

                $tax = $data['tax'];
                $shipping = $data['shipping'];
                $totalAmount = bcadd(bcadd($subtotal, (string) $tax, 2), (string) $shipping, 2);

                $orderNumber = $this->accountingService->generateOrderNumber('sales_orders', 'SO');

                return SalesOrder::create([
                    'orderNumber' => $orderNumber,
                    'customerId' => $customer->id,
                    'customerName' => $data['customerName'],
                    'status' => 'pending',
                    'items' => $verifiedItems,
                    'subtotal' => (float) $subtotal,
                    'tax' => $tax,
                    'shipping' => $shipping,
                    'totalAmount' => (float) $totalAmount,
                    'shippingAddress' => $data['shippingAddress'] ?? [],
                    'paymentMethod' => $data['paymentMethod'] ?? '',
                    'paymentStatus' => 'pending',
                    'notes' => $data['notes'] ?? null,
                ]);
            });

            return response()->json($salesOrder->load('customer'), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => '建立訂單失敗'], 500);
        }
    }
}
