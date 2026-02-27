<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\AccountsReceivable;
use App\Models\ChartOfAccount;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesOrder;
use App\Http\Requests\ShipOrderRequest;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderActionController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
    ) {}

    /**
     * Ship a sales order.
     */
    public function ship(ShipOrderRequest $request, string $id)
    {
        $batchSelections = $request->validated()['batchSelections'];
        $userName = $request->user()?->name ?? '系統管理員';

        try {
            $result = DB::transaction(function () use ($id, $batchSelections, $userName) {
                // 1. Verify order status
                $order = SalesOrder::with('customer')->findOrFail($id);

                if ($order->status !== 'confirmed' && $order->status !== 'processing') {
                    throw new \Exception("訂單狀態不正確，目前為 {$order->status}，需要 confirmed 或 processing");
                }

                $items = $order->items;

                // Preload all products to avoid N+1 queries
                $productIds = collect($items)->pluck('productId')->unique();
                $productsMap = Product::whereIn('id', $productIds)->get()->keyBy('id');

                // 2. Validate stock and batch availability
                foreach ($items as $item) {
                    $product = $productsMap->get($item['productId']);
                    if (!$product) {
                        throw new \Exception("產品 {$item['productName']} 不存在");
                    }
                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("產品 {$item['productName']} 庫存不足：需要 {$item['quantity']}，庫存僅 {$product->stock}");
                    }

                    $selection = collect($batchSelections)->firstWhere('productId', $item['productId']);
                    if (!empty($selection['requiresBatch'])) {
                        $totalSelected = collect($selection['selectedBatches'])->sum('quantity');
                        if ($totalSelected !== $item['quantity']) {
                            throw new \Exception("產品 {$item['productName']} 批次數量不符：需要 {$item['quantity']}，已選 {$totalSelected}");
                        }

                        foreach ($selection['selectedBatches'] as $batchSel) {
                            if (($batchSel['quantity'] ?? 0) <= 0) continue;
                            $batch = ProductBatch::find($batchSel['batchId']);
                            if (!$batch) {
                                throw new \Exception("批號 {$batchSel['batchId']} 不存在");
                            }
                            if ($batch->currentQuantity < $batchSel['quantity']) {
                                throw new \Exception("批號 {$batch->batchNumber} 庫存不足：可用 {$batch->currentQuantity}，需要 {$batchSel['quantity']}");
                            }
                        }
                    }
                }

                // Find accounting accounts
                $receivableAccount = $this->accountingService->findAccount('應收');
                $defaultRevenueAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '銷售'], ['name' => '營收'], ['type' => 'revenue'],
                ]);
                $cogsAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '銷貨成本'], ['code' => '5100'],
                ]);
                $inventoryAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '存貨'], ['code' => '1400'],
                ]);

                // Get customer payment terms
                $paymentDays = 30;
                if ($order->customer && $order->customer->paymentTerms) {
                    $paymentDays = intval($order->customer->paymentTerms) ?: 30;
                }

                $accountTotals = [];
                $totalCost = '0';

                // 3-5. Deduct stock, batches, create inventory movements
                foreach ($items as $item) {
                    $product = $productsMap->get($item['productId']);
                    if (!$product) continue;

                    $beforeStock = $product->stock;

                    // 3. Atomic stock decrement
                    DB::table('products')
                        ->where('id', $item['productId'])
                        ->decrement('stock', $item['quantity']);

                    // 4. Deduct from batches
                    $selection = collect($batchSelections)->firstWhere('productId', $item['productId']);
                    $usedBatches = [];
                    if (!empty($selection['requiresBatch'])) {
                        foreach ($selection['selectedBatches'] as $batchSel) {
                            if (($batchSel['quantity'] ?? 0) <= 0) continue;
                            $batch = ProductBatch::find($batchSel['batchId']);
                            DB::table('product_batches')
                                ->where('id', $batchSel['batchId'])
                                ->decrement('currentQuantity', $batchSel['quantity']);
                            $usedBatches[] = ($batch->batchNumber ?? $batchSel['batchId']) . "({$batchSel['quantity']})";
                        }
                    }

                    // Calculate cost
                    $itemCost = bcmul((string) ($product->costPrice ?? 0), (string) $item['quantity'], 2);
                    $totalCost = bcadd($totalCost, $itemCost, 2);

                    // 5. Create inventory movement
                    $batchInfo = count($usedBatches) > 0 ? ' - 批號: ' . implode(', ', $usedBatches) : '';
                    InventoryMovement::create([
                        'productId' => $item['productId'],
                        'productName' => $item['productName'],
                        'type' => 'out',
                        'quantity' => $item['quantity'],
                        'beforeStock' => $beforeStock,
                        'afterStock' => $beforeStock - $item['quantity'],
                        'reason' => "銷售出貨 - 訂單 {$order->orderNumber}{$batchInfo}",
                        'reference' => $id,
                        'createdBy' => $userName,
                    ]);

                    // Track revenue by account
                    $salesAccount = $defaultRevenueAccount;
                    if ($product->salesAccountId) {
                        $productAccount = ChartOfAccount::find($product->salesAccountId);
                        if ($productAccount) $salesAccount = $productAccount;
                    }
                    if ($salesAccount) {
                        if (!isset($accountTotals[$salesAccount->id])) {
                            $accountTotals[$salesAccount->id] = [
                                'accountId' => $salesAccount->id,
                                'accountName' => $salesAccount->name,
                                'amount' => '0',
                            ];
                        }
                        $accountTotals[$salesAccount->id]['amount'] = bcadd(
                            $accountTotals[$salesAccount->id]['amount'],
                            (string) ($item['totalPrice'] ?? bcmul((string) $item['price'], (string) $item['quantity'], 2)),
                            2
                        );
                    }
                }

                // 6. Create accounts receivable
                $dueDate = now()->addDays($paymentDays);
                AccountsReceivable::create([
                    'customerId' => $order->customerId,
                    'customerName' => $order->customerName,
                    'orderId' => $id,
                    'orderNumber' => $order->orderNumber,
                    'invoiceNumber' => $order->orderNumber,
                    'amount' => (float) $order->totalAmount,
                    'paidAmount' => $order->paymentStatus === 'paid' ? (float) $order->totalAmount : 0,
                    'dueDate' => $dueDate,
                    'status' => $order->paymentStatus === 'paid' ? 'paid' : 'pending',
                ]);

                // 7. Create revenue voucher + journal entry
                if ($receivableAccount && count($accountTotals) > 0) {
                    $voucherLines = [
                        [
                            'accountId' => $receivableAccount->id,
                            'accountCode' => $receivableAccount->code,
                            'accountName' => $receivableAccount->name,
                            'debit' => (float) $order->totalAmount,
                            'credit' => 0,
                            'description' => "銷售出貨 - {$order->customerName}",
                        ],
                    ];

                    foreach ($accountTotals as $acc) {
                        $voucherLines[] = [
                            'accountId' => $acc['accountId'],
                            'accountCode' => '',
                            'accountName' => $acc['accountName'],
                            'debit' => 0,
                            'credit' => (float) $acc['amount'],
                            'description' => '銷貨收入',
                        ];
                    }

                    // Add shipping fee entry
                    if (bccomp((string) $order->shipping, '0', 2) > 0) {
                        $shippingAccount = $this->accountingService->findAccountByConditions([
                            ['name' => '運費'], ['name' => '營業費用'],
                        ]);
                        if ($shippingAccount) {
                            $voucherLines[] = [
                                'accountId' => $shippingAccount->id,
                                'accountCode' => $shippingAccount->code,
                                'accountName' => $shippingAccount->name,
                                'debit' => 0,
                                'credit' => (float) $order->shipping,
                                'description' => '運費收入',
                            ];
                        }
                    }

                    // Add tax entry
                    if (bccomp((string) $order->tax, '0', 2) > 0) {
                        $taxAccount = $this->accountingService->findAccountByConditions([
                            ['name' => '營業稅'], ['name' => '銷項稅'],
                        ]);
                        if ($taxAccount) {
                            $voucherLines[] = [
                                'accountId' => $taxAccount->id,
                                'accountCode' => $taxAccount->code,
                                'accountName' => $taxAccount->name,
                                'debit' => 0,
                                'credit' => (float) $order->tax,
                                'description' => '銷項稅額',
                            ];
                        }
                    }

                    $totalDebit = array_reduce($voucherLines, fn($s, $l) => bcadd($s, (string) $l['debit'], 2), '0');
                    $totalCredit = array_reduce($voucherLines, fn($s, $l) => bcadd($s, (string) $l['credit'], 2), '0');

                    // Balance the voucher
                    $diff = bcsub($totalDebit, $totalCredit, 2);
                    if (abs((float) $diff) > 0.01) {
                        $fallbackAccount = $defaultRevenueAccount ?? $receivableAccount;
                        if (bccomp($diff, '0', 2) > 0) {
                            $voucherLines[] = [
                                'accountId' => $fallbackAccount->id,
                                'accountCode' => $fallbackAccount->code ?? '',
                                'accountName' => $fallbackAccount->name,
                                'debit' => 0,
                                'credit' => (float) $diff,
                                'description' => '其他收入（運費/稅金）',
                            ];
                            $totalCredit = bcadd($totalCredit, $diff, 2);
                        } else {
                            $absDiff = bcmul($diff, '-1', 2);
                            $voucherLines[] = [
                                'accountId' => $fallbackAccount->id,
                                'accountCode' => $fallbackAccount->code ?? '',
                                'accountName' => $fallbackAccount->name,
                                'debit' => (float) $absDiff,
                                'credit' => 0,
                                'description' => '折讓調整',
                            ];
                            $totalDebit = bcadd($totalDebit, $absDiff, 2);
                        }
                    }

                    $revenueVoucherNumber = $this->accountingService->generateVoucherNumber('T');

                    $this->accountingService->createVoucherAndJournal([
                        'voucherNumber' => $revenueVoucherNumber,
                        'voucherType' => 'transfer',
                        'voucherDate' => now(),
                        'description' => "銷售出貨 - {$order->customerName} - 訂單 {$order->orderNumber}",
                        'reference' => "SO-{$order->orderNumber}",
                    ], $voucherLines, $userName);

                    // 8. Atomic account balance updates
                    $this->accountingService->updateAccountBalance($receivableAccount->id, (float) $order->totalAmount, 'increment');
                    foreach ($accountTotals as $acc) {
                        $this->accountingService->updateAccountBalance($acc['accountId'], (float) $acc['amount'], 'increment');
                    }

                    if (bccomp((string) $order->shipping, '0', 2) > 0) {
                        $shippingAccount = $this->accountingService->findAccountByConditions([
                            ['name' => '運費'], ['name' => '營業費用'],
                        ]);
                        if ($shippingAccount) {
                            $this->accountingService->updateAccountBalance($shippingAccount->id, (float) $order->shipping, 'increment');
                        }
                    }

                    if (bccomp((string) $order->tax, '0', 2) > 0) {
                        $taxAccount = $this->accountingService->findAccountByConditions([
                            ['name' => '營業稅'], ['name' => '銷項稅'],
                        ]);
                        if ($taxAccount) {
                            $this->accountingService->updateAccountBalance($taxAccount->id, (float) $order->tax, 'increment');
                        }
                    }
                }

                // 9. Create COGS voucher + journal entry
                if (bccomp($totalCost, '0', 2) > 0 && $cogsAccount && $inventoryAccount) {
                    $cogsVoucherLines = [
                        [
                            'accountId' => $cogsAccount->id,
                            'accountCode' => $cogsAccount->code,
                            'accountName' => $cogsAccount->name,
                            'debit' => (float) $totalCost,
                            'credit' => 0,
                            'description' => "銷貨成本 - 訂單 {$order->orderNumber}",
                        ],
                        [
                            'accountId' => $inventoryAccount->id,
                            'accountCode' => $inventoryAccount->code,
                            'accountName' => $inventoryAccount->name,
                            'debit' => 0,
                            'credit' => (float) $totalCost,
                            'description' => "存貨減少 - 訂單 {$order->orderNumber}",
                        ],
                    ];

                    $cogsVoucherNumber = $this->accountingService->generateVoucherNumber('T');

                    $this->accountingService->createVoucherAndJournal([
                        'voucherNumber' => $cogsVoucherNumber,
                        'voucherType' => 'transfer',
                        'voucherDate' => now(),
                        'description' => "銷貨成本 - {$order->customerName} - 訂單 {$order->orderNumber}",
                        'reference' => "SO-COGS-{$order->orderNumber}",
                    ], $cogsVoucherLines, $userName);

                    $this->accountingService->updateAccountBalance($cogsAccount->id, (float) $totalCost, 'increment');
                    $this->accountingService->updateAccountBalance($inventoryAccount->id, (float) $totalCost, 'decrement');
                }

                // 10. Update order status
                $order->update(['status' => 'shipped']);

                return $order->load('customer');
            });

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Return a sales order.
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
                $order = SalesOrder::with('customer')->findOrFail($id);

                if ($order->status !== 'shipped' && $order->status !== 'delivered') {
                    throw new \Exception("CONFLICT:訂單狀態不正確，目前為 {$order->status}，需要 shipped 或 delivered");
                }

                $items = $order->items;

                // Find accounting accounts
                $receivableAccount = $this->accountingService->findAccount('應收');
                if (!$receivableAccount) throw new \Exception('CONFLICT:找不到應收帳款科目，請先建立會計科目');

                $defaultRevenueAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '銷售'], ['name' => '營收'], ['type' => 'revenue'],
                ]);
                if (!$defaultRevenueAccount) throw new \Exception('CONFLICT:找不到銷售收入科目，請先建立會計科目');

                $cogsAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '銷貨成本'], ['code' => '5100'],
                ]);
                $inventoryAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '存貨'], ['code' => '1400'],
                ]);

                $accountTotals = [];
                $totalCost = '0';

                // Preload products to avoid N+1 queries
                $productIds = collect($items)->pluck('productId')->unique();
                $productsMap = Product::whereIn('id', $productIds)->get()->keyBy('id');

                // 2-4. Restore stock, batches, create inventory movements
                foreach ($items as $item) {
                    $product = $productsMap->get($item['productId']);
                    if (!$product) continue;

                    $beforeStock = $product->stock;

                    // Restore stock
                    DB::table('products')
                        ->where('id', $item['productId'])
                        ->increment('stock', $item['quantity']);

                    // Calculate cost
                    $itemCost = bcmul((string) ($product->costPrice ?? 0), (string) $item['quantity'], 2);
                    $totalCost = bcadd($totalCost, $itemCost, 2);

                    // Restore batch quantities
                    $movements = InventoryMovement::where('reference', $id)
                        ->where('productId', $item['productId'])
                        ->where('type', 'out')
                        ->get();

                    foreach ($movements as $movement) {
                        if (preg_match('/批號: (.+)/', $movement->reason, $batchMatch)) {
                            $batchEntries = explode(', ', $batchMatch[1]);
                            foreach ($batchEntries as $entry) {
                                if (preg_match('/(.+)\((\d+)\)/', $entry, $match)) {
                                    $batchNumber = $match[1];
                                    $qty = (int) $match[2];
                                    $batch = ProductBatch::where('batchNumber', $batchNumber)
                                        ->where('productId', $item['productId'])
                                        ->first();
                                    if ($batch) {
                                        DB::table('product_batches')
                                            ->where('id', $batch->id)
                                            ->increment('currentQuantity', $qty);
                                    }
                                }
                            }
                        }
                    }

                    // Create return inventory movement
                    InventoryMovement::create([
                        'productId' => $item['productId'],
                        'productName' => $item['productName'],
                        'type' => 'in',
                        'quantity' => $item['quantity'],
                        'beforeStock' => $beforeStock,
                        'afterStock' => $beforeStock + $item['quantity'],
                        'reason' => "銷售退貨 - {$returnReason}",
                        'reference' => $id,
                        'createdBy' => $userName,
                    ]);

                    // Track revenue by account
                    $salesAccount = $defaultRevenueAccount;
                    if ($product->salesAccountId) {
                        $productAccount = ChartOfAccount::find($product->salesAccountId);
                        if ($productAccount) $salesAccount = $productAccount;
                    }

                    if (!isset($accountTotals[$salesAccount->id])) {
                        $accountTotals[$salesAccount->id] = [
                            'accountId' => $salesAccount->id,
                            'accountName' => $salesAccount->name,
                            'accountCode' => $salesAccount->code ?? '',
                            'amount' => '0',
                        ];
                    }
                    $accountTotals[$salesAccount->id]['amount'] = bcadd(
                        $accountTotals[$salesAccount->id]['amount'],
                        (string) ($item['totalPrice'] ?? bcmul((string) $item['price'], (string) $item['quantity'], 2)),
                        2
                    );
                }

                // 5. Create return voucher
                $voucherLines = [];
                foreach ($accountTotals as $acc) {
                    $voucherLines[] = [
                        'accountId' => $acc['accountId'],
                        'accountCode' => $acc['accountCode'],
                        'accountName' => $acc['accountName'],
                        'debit' => (float) $acc['amount'],
                        'credit' => 0,
                        'description' => '銷貨退回',
                    ];
                }

                // Reverse shipping fee
                if (bccomp((string) $order->shipping, '0', 2) > 0) {
                    $shippingAccount = $this->accountingService->findAccountByConditions([
                        ['name' => '運費'], ['name' => '營業費用'],
                    ]);
                    if ($shippingAccount) {
                        $voucherLines[] = [
                            'accountId' => $shippingAccount->id,
                            'accountCode' => $shippingAccount->code,
                            'accountName' => $shippingAccount->name,
                            'debit' => (float) $order->shipping,
                            'credit' => 0,
                            'description' => '退回運費',
                        ];
                    }
                }

                // Reverse tax
                if (bccomp((string) $order->tax, '0', 2) > 0) {
                    $taxAccount = $this->accountingService->findAccountByConditions([
                        ['name' => '營業稅'], ['name' => '銷項稅'],
                    ]);
                    if ($taxAccount) {
                        $voucherLines[] = [
                            'accountId' => $taxAccount->id,
                            'accountCode' => $taxAccount->code,
                            'accountName' => $taxAccount->name,
                            'debit' => (float) $order->tax,
                            'credit' => 0,
                            'description' => '退回銷項稅',
                        ];
                    }
                }

                $voucherLines[] = [
                    'accountId' => $receivableAccount->id,
                    'accountCode' => $receivableAccount->code,
                    'accountName' => $receivableAccount->name,
                    'debit' => 0,
                    'credit' => (float) $order->totalAmount,
                    'description' => "沖銷應收帳款 - {$order->customerName}",
                ];

                $returnVoucherNumber = $this->accountingService->generateVoucherNumber('T');

                $this->accountingService->createVoucherAndJournal([
                    'voucherNumber' => $returnVoucherNumber,
                    'voucherType' => 'transfer',
                    'voucherDate' => now(),
                    'description' => "銷售退貨 - {$order->customerName} - 訂單 {$order->orderNumber} - {$returnReason}",
                    'reference' => "SO-RET-{$order->orderNumber}",
                ], $voucherLines, $userName);

                // 6. Update account balances (reverse)
                foreach ($accountTotals as $acc) {
                    $this->accountingService->updateAccountBalance($acc['accountId'], (float) $acc['amount'], 'decrement');
                }
                $this->accountingService->updateAccountBalance($receivableAccount->id, (float) $order->totalAmount, 'decrement');

                // Reverse shipping
                if (bccomp((string) $order->shipping, '0', 2) > 0) {
                    $shippingAccount = $this->accountingService->findAccountByConditions([
                        ['name' => '運費'], ['name' => '營業費用'],
                    ]);
                    if ($shippingAccount) {
                        $this->accountingService->updateAccountBalance($shippingAccount->id, (float) $order->shipping, 'decrement');
                    }
                }

                // Reverse tax
                if (bccomp((string) $order->tax, '0', 2) > 0) {
                    $taxAccount = $this->accountingService->findAccountByConditions([
                        ['name' => '營業稅'], ['name' => '銷項稅'],
                    ]);
                    if ($taxAccount) {
                        $this->accountingService->updateAccountBalance($taxAccount->id, (float) $order->tax, 'decrement');
                    }
                }

                // Reverse COGS
                if (bccomp($totalCost, '0', 2) > 0 && $cogsAccount && $inventoryAccount) {
                    $cogsReversalLines = [
                        [
                            'accountId' => $inventoryAccount->id,
                            'accountCode' => $inventoryAccount->code,
                            'accountName' => $inventoryAccount->name,
                            'debit' => (float) $totalCost,
                            'credit' => 0,
                            'description' => "存貨回沖 - 訂單 {$order->orderNumber}",
                        ],
                        [
                            'accountId' => $cogsAccount->id,
                            'accountCode' => $cogsAccount->code,
                            'accountName' => $cogsAccount->name,
                            'debit' => 0,
                            'credit' => (float) $totalCost,
                            'description' => "銷貨成本回沖 - 訂單 {$order->orderNumber}",
                        ],
                    ];

                    $cogsVoucherNumber = $this->accountingService->generateVoucherNumber('T');

                    $this->accountingService->createVoucherAndJournal([
                        'voucherNumber' => $cogsVoucherNumber,
                        'voucherType' => 'transfer',
                        'voucherDate' => now(),
                        'description' => "銷貨成本回沖 - {$order->customerName} - 訂單 {$order->orderNumber}",
                        'reference' => "SO-RET-COGS-{$order->orderNumber}",
                    ], $cogsReversalLines, $userName);

                    $this->accountingService->updateAccountBalance($inventoryAccount->id, (float) $totalCost, 'increment');
                    $this->accountingService->updateAccountBalance($cogsAccount->id, (float) $totalCost, 'decrement');
                }

                // Update/cancel AR
                $relatedAR = AccountsReceivable::where('invoiceNumber', $order->orderNumber)->first();
                if ($relatedAR) {
                    $relatedAR->update(['status' => 'paid', 'paidAmount' => $relatedAR->amount]);
                }

                // Update order status
                $order->update([
                    'status' => 'returned',
                    'returnedDate' => now(),
                    'returnReason' => $returnReason,
                    'paymentStatus' => $order->paymentStatus === 'paid' ? 'refunded' : 'pending',
                ]);

                return $order->load('customer');
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
     * Refund a sales order.
     */
    public function refund(Request $request, string $id)
    {
        $userName = $request->user()?->name ?? '系統';

        try {
            $result = DB::transaction(function () use ($id, $request, $userName) {
                $order = SalesOrder::findOrFail($id);

                if ($order->paymentStatus === 'refunded') {
                    throw new \Exception('CONFLICT:此訂單已退款');
                }
                if ($order->status !== 'returned' && $order->paymentStatus !== 'paid') {
                    throw new \Exception('CONFLICT:訂單狀態不允許退款（需要已退貨或已付款狀態）');
                }

                $refundAmount = $request->input('refundAmount', (float) $order->totalAmount);

                // Find accounting accounts
                $cashAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '現金'], ['name' => '銀行'],
                ]);
                $refundExpenseAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '退款'], ['name' => '銷貨退回'], ['type' => 'expense'],
                ]);

                if ($cashAccount) {
                    $debitAccount = $refundExpenseAccount ?? $cashAccount;
                    $voucherLines = [
                        [
                            'accountId' => $debitAccount->id,
                            'accountCode' => $debitAccount->code,
                            'accountName' => $debitAccount->name,
                            'debit' => $refundAmount,
                            'credit' => 0,
                            'description' => "退款支出 - {$order->customerName}",
                        ],
                        [
                            'accountId' => $cashAccount->id,
                            'accountCode' => $cashAccount->code,
                            'accountName' => $cashAccount->name,
                            'debit' => 0,
                            'credit' => $refundAmount,
                            'description' => "支付退款 - 訂單 {$order->orderNumber}",
                        ],
                    ];

                    $voucherNumber = $this->accountingService->generateVoucherNumber('P');

                    $this->accountingService->createVoucherAndJournal([
                        'voucherNumber' => $voucherNumber,
                        'voucherType' => 'payment',
                        'voucherDate' => now(),
                        'description' => "銷售退款 - {$order->customerName} - 訂單 {$order->orderNumber}",
                        'reference' => "SO-REFUND-{$order->orderNumber}",
                    ], $voucherLines, $userName);

                    if ($refundExpenseAccount) {
                        $this->accountingService->updateAccountBalance($refundExpenseAccount->id, $refundAmount, 'increment');
                    }
                    $this->accountingService->updateAccountBalance($cashAccount->id, $refundAmount, 'decrement');
                }

                $order->update(['paymentStatus' => 'refunded']);

                $ar = AccountsReceivable::where('orderId', $id)->first();
                if ($ar) {
                    $ar->update(['status' => 'refunded']);
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

