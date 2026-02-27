<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\AccountsPayable;
use App\Models\ChartOfAccount;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Http\Requests\ReceiveOrderRequest;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderActionController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
    ) {}

    /**
     * Receive a purchase order.
     */
    public function receive(ReceiveOrderRequest $request, string $id)
    {
        $batchEntries = $request->validated()['batchEntries'];
        $userName = $request->user()?->name ?? '系統管理員';

        try {
            $result = DB::transaction(function () use ($id, $batchEntries, $userName) {
                // 1. Verify order status
                $order = PurchaseOrder::with('supplier')->findOrFail($id);

                if ($order->status !== 'approved') {
                    throw new \Exception("採購單狀態不正確，目前為 {$order->status}，需要 approved");
                }

                $today = now()->toDateString();

                // Get supplier payment terms
                $paymentDays = 30;
                if ($order->supplier && $order->supplier->paymentTerms) {
                    $paymentDays = intval($order->supplier->paymentTerms) ?: 30;
                }

                // Preload products to avoid N+1 queries
                $entryProductIds = collect($batchEntries)->pluck('productId')->unique();
                $productsMap = Product::whereIn('id', $entryProductIds)->get()->keyBy('id');

                // 2-4. Update stock, create batches, create inventory movements
                foreach ($batchEntries as $entry) {
                    $product = $productsMap->get($entry['productId']);
                    if (!$product) {
                        throw new \Exception("產品 {$entry['productName']} 不存在");
                    }

                    $conversionFactor = isset($entry['unitConversionFactor']) ? (int) $entry['unitConversionFactor'] : 1;
                    $actualQty = $entry['quantity'] * $conversionFactor;

                    $beforeStock = $product->stock;

                    // 2. Atomic stock increment
                    DB::table('products')
                        ->where('id', $entry['productId'])
                        ->increment('stock', $actualQty);

                    // 3. Create product batch (if requires batch)
                    if (!empty($entry['requiresBatch'])) {
                        ProductBatch::create([
                            'productId' => $entry['productId'],
                            'productName' => $entry['productName'],
                            'batchNumber' => $entry['batchNumber'],
                            'manufacturingDate' => !empty($entry['manufacturingDate']) ? $entry['manufacturingDate'] : null,
                            'expirationDate' => !empty($entry['expirationDate']) ? $entry['expirationDate'] : null,
                            'receivedDate' => $today,
                            'initialQuantity' => $actualQty,
                            'currentQuantity' => $actualQty,
                            'reservedQuantity' => 0,
                            'supplierId' => $order->supplierId,
                            'supplierName' => $order->supplierName,
                            'purchaseOrderId' => $order->id,
                            'purchaseOrderNumber' => $order->orderNumber,
                            'costPrice' => $entry['unitPrice'] ?? 0,
                            'notes' => "採購單 {$order->orderNumber}",
                        ]);
                    }

                    // 4. Create inventory movement
                    $reason = !empty($entry['requiresBatch'])
                        ? "採購入庫 - 採購單 {$order->orderNumber} - 批號 {$entry['batchNumber']}"
                        : "採購入庫 - 採購單 {$order->orderNumber}";

                    InventoryMovement::create([
                        'productId' => $entry['productId'],
                        'productName' => $entry['productName'],
                        'type' => 'in',
                        'quantity' => $actualQty,
                        'beforeStock' => $beforeStock,
                        'afterStock' => $beforeStock + $actualQty,
                        'reason' => $reason,
                        'reference' => $id,
                        'createdBy' => $userName,
                    ]);
                }

                // 5. Create accounts payable
                $dueDate = now()->addDays($paymentDays);
                AccountsPayable::create([
                    'supplierId' => $order->supplierId,
                    'supplierName' => $order->supplierName,
                    'orderId' => $id,
                    'orderNumber' => $order->orderNumber,
                    'invoiceNumber' => "AP-{$order->orderNumber}",
                    'amount' => (float) $order->totalAmount,
                    'paidAmount' => 0,
                    'dueDate' => $dueDate,
                    'status' => 'pending',
                ]);

                // Find accounting accounts
                $payableAccount = $this->accountingService->findAccount('應付');
                $defaultPurchaseAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '進貨'], ['name' => '存貨'], ['type' => 'expense'],
                ]);

                $orderItems = $order->items;
                $accountTotals = [];

                $orderProductIds = collect($orderItems)->pluck('productId')->unique();
                $orderProducts = Product::whereIn('id', $orderProductIds)->get()->keyBy('id');

                foreach ($orderItems as $item) {
                    $product = $orderProducts->get($item['productId']);
                    $purchaseAccount = $defaultPurchaseAccount;
                    if ($product && $product->purchaseAccountId) {
                        $productAccount = ChartOfAccount::find($product->purchaseAccountId);
                        if ($productAccount) $purchaseAccount = $productAccount;
                    }
                    if ($purchaseAccount) {
                        if (!isset($accountTotals[$purchaseAccount->id])) {
                            $accountTotals[$purchaseAccount->id] = [
                                'accountId' => $purchaseAccount->id,
                                'accountName' => $purchaseAccount->name,
                                'amount' => '0',
                            ];
                        }
                        $accountTotals[$purchaseAccount->id]['amount'] = bcadd(
                            $accountTotals[$purchaseAccount->id]['amount'],
                            (string) ($item['totalPrice'] ?? bcmul((string) ($item['unitPrice'] ?? 0), (string) $item['quantity'], 2)),
                            2
                        );
                    }
                }

                // 6. Create purchase voucher + journal entry
                if (count($accountTotals) > 0 && $payableAccount) {
                    $voucherLines = [];

                    foreach ($accountTotals as $accountTotal) {
                        $account = ChartOfAccount::find($accountTotal['accountId']);
                        $voucherLines[] = [
                            'accountId' => $accountTotal['accountId'],
                            'accountCode' => $account->code ?? '',
                            'accountName' => $accountTotal['accountName'],
                            'debit' => (float) $accountTotal['amount'],
                            'credit' => 0,
                            'description' => '採購進貨',
                        ];
                    }

                    $voucherLines[] = [
                        'accountId' => $payableAccount->id,
                        'accountCode' => $payableAccount->code,
                        'accountName' => $payableAccount->name,
                        'debit' => 0,
                        'credit' => (float) $order->totalAmount,
                        'description' => "應付帳款 - {$order->supplierName}",
                    ];

                    $voucherNumber = $this->accountingService->generateVoucherNumber('T');

                    $this->accountingService->createVoucherAndJournal([
                        'voucherNumber' => $voucherNumber,
                        'voucherType' => 'transfer',
                        'voucherDate' => now(),
                        'description' => "採購入庫 - {$order->supplierName} - 採購單 {$order->orderNumber}",
                        'reference' => "PO-{$order->orderNumber}",
                    ], $voucherLines, $userName);

                    // 7. Atomic account balance updates
                    foreach ($accountTotals as $accountTotal) {
                        $this->accountingService->updateAccountBalance($accountTotal['accountId'], (float) $accountTotal['amount'], 'increment');
                    }
                    $this->accountingService->updateAccountBalance($payableAccount->id, (float) $order->totalAmount, 'increment');
                }

                // 8. Update order status
                $order->update([
                    'status' => 'received',
                    'receivedDate' => now(),
                ]);

                return $order->load('supplier');
            });

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Return a purchase order.
     */
    public function returnOrder(Request $request, string $id)
    {
        $returnReason = $request->input('returnReason');

        if (!$returnReason || !trim($returnReason)) {
            return response()->json(['message' => '請輸入退貨原因'], 400);
        }

        $userName = $request->user()?->name ?? '系統';

        try {
            $result = DB::transaction(function () use ($id, $returnReason, $userName) {
                // 1. Validate order
                $order = PurchaseOrder::with('supplier')->findOrFail($id);

                if ($order->status !== 'received') {
                    throw new \Exception("CONFLICT:採購單狀態不正確，目前為 {$order->status}，需要 received");
                }

                $items = $order->items;

                // Find accounting accounts
                $payableAccount = $this->accountingService->findAccount('應付');
                if (!$payableAccount) throw new \Exception('CONFLICT:找不到應付帳款科目，請先建立會計科目');

                $defaultPurchaseAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '進貨'], ['name' => '存貨'], ['type' => 'expense'],
                ]);
                if (!$defaultPurchaseAccount) throw new \Exception('CONFLICT:找不到進貨/存貨科目，請先建立會計科目');

                $accountTotals = [];

                // 2. Check stock and deduct for each item
                foreach ($items as $item) {
                    $product = Product::find($item['productId']);
                    if (!$product) continue;

                    $conversionFactor = isset($item['unitConversionFactor']) ? (int) $item['unitConversionFactor'] : 1;
                    $actualQty = $item['quantity'] * $conversionFactor;

                    if ($product->stock < $actualQty) {
                        throw new \Exception("CONFLICT:產品 {$item['productName']} 庫存不足，無法退貨（庫存 {$product->stock}，需退 {$actualQty}）");
                    }

                    $beforeStock = $product->stock;

                    DB::table('products')
                        ->where('id', $item['productId'])
                        ->decrement('stock', $actualQty);

                    // Clean up batch records
                    $relatedBatches = ProductBatch::where('purchaseOrderId', $id)
                        ->where('productId', $item['productId'])
                        ->orderBy('createdAt', 'desc')
                        ->get();

                    $remainingToReturn = $actualQty;
                    foreach ($relatedBatches as $batch) {
                        if ($remainingToReturn <= 0) break;
                        if ($batch->currentQuantity <= $remainingToReturn) {
                            $remainingToReturn -= $batch->currentQuantity;
                            $batch->update(['currentQuantity' => 0, 'status' => 'depleted']);
                        } else {
                            DB::table('product_batches')
                                ->where('id', $batch->id)
                                ->decrement('currentQuantity', $remainingToReturn);
                            $remainingToReturn = 0;
                        }
                    }

                    // 3. Create inventory movement
                    InventoryMovement::create([
                        'productId' => $item['productId'],
                        'productName' => $item['productName'],
                        'type' => 'out',
                        'quantity' => $actualQty,
                        'beforeStock' => $beforeStock,
                        'afterStock' => $beforeStock - $actualQty,
                        'reason' => "採購退貨 - {$order->orderNumber} - {$returnReason}",
                        'reference' => $id,
                        'createdBy' => $userName,
                    ]);

                    // Track amounts by purchase account
                    $purchaseAccount = $defaultPurchaseAccount;
                    if ($product->purchaseAccountId) {
                        $productAccount = ChartOfAccount::find($product->purchaseAccountId);
                        if ($productAccount) $purchaseAccount = $productAccount;
                    }

                    if (!isset($accountTotals[$purchaseAccount->id])) {
                        $accountTotals[$purchaseAccount->id] = [
                            'accountId' => $purchaseAccount->id,
                            'accountName' => $purchaseAccount->name,
                            'accountCode' => $purchaseAccount->code ?? '',
                            'amount' => '0',
                        ];
                    }
                    $accountTotals[$purchaseAccount->id]['amount'] = bcadd(
                        $accountTotals[$purchaseAccount->id]['amount'],
                        (string) ($item['totalPrice'] ?? bcmul((string) ($item['unitPrice'] ?? 0), (string) $item['quantity'], 2)),
                        2
                    );
                }

                // 4. Create return voucher
                $voucherLines = [];

                // Debit AP
                $voucherLines[] = [
                    'accountId' => $payableAccount->id,
                    'accountCode' => $payableAccount->code,
                    'accountName' => $payableAccount->name,
                    'debit' => (float) $order->totalAmount,
                    'credit' => 0,
                    'description' => "沖銷應付帳款 - {$order->supplierName}",
                ];

                // Credit purchase/inventory accounts
                foreach ($accountTotals as $acc) {
                    $voucherLines[] = [
                        'accountId' => $acc['accountId'],
                        'accountCode' => $acc['accountCode'],
                        'accountName' => $acc['accountName'],
                        'debit' => 0,
                        'credit' => (float) $acc['amount'],
                        'description' => '採購退回',
                    ];
                }

                $totalDebit = array_reduce($voucherLines, fn($s, $l) => bcadd($s, (string) $l['debit'], 2), '0');
                $totalCredit = array_reduce($voucherLines, fn($s, $l) => bcadd($s, (string) $l['credit'], 2), '0');

                // Balance if needed
                $diff = bcsub($totalDebit, $totalCredit, 2);
                if (abs((float) $diff) > 0.01) {
                    if (bccomp($diff, '0', 2) > 0) {
                        $voucherLines[] = [
                            'accountId' => $defaultPurchaseAccount->id,
                            'accountCode' => $defaultPurchaseAccount->code ?? '',
                            'accountName' => $defaultPurchaseAccount->name,
                            'debit' => 0,
                            'credit' => (float) $diff,
                            'description' => '退貨差額調整',
                        ];
                    } else {
                        $absDiff = bcmul($diff, '-1', 2);
                        $voucherLines[] = [
                            'accountId' => $defaultPurchaseAccount->id,
                            'accountCode' => $defaultPurchaseAccount->code ?? '',
                            'accountName' => $defaultPurchaseAccount->name,
                            'debit' => (float) $absDiff,
                            'credit' => 0,
                            'description' => '退貨差額調整',
                        ];
                    }
                }

                $returnVoucherNumber = $this->accountingService->generateVoucherNumber('T');

                $this->accountingService->createVoucherAndJournal([
                    'voucherNumber' => $returnVoucherNumber,
                    'voucherType' => 'transfer',
                    'voucherDate' => now(),
                    'description' => "採購退貨 - {$order->supplierName} - 採購單 {$order->orderNumber} - {$returnReason}",
                    'reference' => "PO-RET-{$order->orderNumber}",
                ], $voucherLines, $userName);

                // 5. Update account balances (reverse)
                $this->accountingService->updateAccountBalance($payableAccount->id, (float) $order->totalAmount, 'decrement');
                foreach ($accountTotals as $acc) {
                    $this->accountingService->updateAccountBalance($acc['accountId'], (float) $acc['amount'], 'decrement');
                }

                // Update/cancel AP
                $relatedAP = AccountsPayable::where('orderId', $id)->first();
                if ($relatedAP) {
                    $relatedAP->update(['status' => 'paid', 'paidAmount' => $relatedAP->amount]);
                }

                // 6. Update order status
                $order->update([
                    'status' => 'returned',
                    'returnedDate' => now(),
                    'returnReason' => $returnReason,
                ]);

                return $order->load('supplier');
            });

            return response()->json($result);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_starts_with($message, 'CONFLICT:')) {
                return response()->json(['message' => str_replace('CONFLICT:', '', $message)], 409);
            }

            return response()->json(['message' => $message], 400);
        }
    }

    /**
     * Refund a purchase order.
     */
    public function refund(Request $request, string $id)
    {
        $userName = $request->user()?->name ?? '系統';

        try {
            $result = DB::transaction(function () use ($id, $request, $userName) {
                $order = PurchaseOrder::findOrFail($id);

                if ($order->refundReceived) {
                    throw new \Exception('CONFLICT:此採購單已收到退款');
                }
                if ($order->status !== 'returned') {
                    throw new \Exception('CONFLICT:採購單狀態不允許退款（需要已退貨狀態）');
                }

                $refundAmount = $request->input('refundAmount', (float) $order->totalAmount);

                // Find accounting accounts
                $cashAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '現金'], ['name' => '銀行'],
                ]);
                $refundIncomeAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '進貨退回'], ['name' => '其他收入'], ['type' => 'revenue'],
                ]);

                if ($cashAccount) {
                    $creditAccount = $refundIncomeAccount ?? $cashAccount;
                    $voucherLines = [
                        [
                            'accountId' => $cashAccount->id,
                            'accountCode' => $cashAccount->code,
                            'accountName' => $cashAccount->name,
                            'debit' => $refundAmount,
                            'credit' => 0,
                            'description' => "收到退款 - {$order->supplierName}",
                        ],
                        [
                            'accountId' => $creditAccount->id,
                            'accountCode' => $creditAccount->code,
                            'accountName' => $creditAccount->name,
                            'debit' => 0,
                            'credit' => $refundAmount,
                            'description' => "進貨退回收入 - 採購單 {$order->orderNumber}",
                        ],
                    ];

                    $voucherNumber = $this->accountingService->generateVoucherNumber('R');

                    $this->accountingService->createVoucherAndJournal([
                        'voucherNumber' => $voucherNumber,
                        'voucherType' => 'receipt',
                        'voucherDate' => now(),
                        'description' => "採購退款收回 - {$order->supplierName} - 採購單 {$order->orderNumber}",
                        'reference' => "PO-REFUND-{$order->orderNumber}",
                    ], $voucherLines, $userName);

                    $this->accountingService->updateAccountBalance($cashAccount->id, $refundAmount, 'increment');
                    if ($refundIncomeAccount) {
                        $this->accountingService->updateAccountBalance($refundIncomeAccount->id, $refundAmount, 'increment');
                    }
                }

                $order->update(['refundReceived' => true]);

                $ap = AccountsPayable::where('orderId', $id)->first();
                if ($ap) {
                    $ap->update(['status' => 'refunded']);
                }

                return $order;
            });

            return response()->json($result);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_starts_with($message, 'CONFLICT:')) {
                return response()->json(['message' => str_replace('CONFLICT:', '', $message)], 409);
            }

            return response()->json(['message' => $message], 400);
        }
    }
}
