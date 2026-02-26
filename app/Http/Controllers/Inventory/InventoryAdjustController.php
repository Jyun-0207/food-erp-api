<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockCount;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryAdjustController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
    ) {}

    public function adjust(Request $request)
    {
        try {
            $body = $request->all();
            $userName = $request->user()?->name ?? '系統管理員';

            $result = DB::transaction(function () use ($body, $userName) {
                $adjustments = [];

                if (!empty($body['adjustments'])) {
                    $adjustments = $body['adjustments'];
                } elseif (!empty($body['productId'])) {
                    $product = Product::find($body['productId']);
                    if (!$product) {
                        throw new \Exception('NOT_FOUND:找不到產品');
                    }
                    $adjustType = $body['adjustType'] ?? 'set';
                    $quantity = $body['quantity'] ?? 0;
                    $newQuantity = match ($adjustType) {
                        'add' => $product->stock + $quantity,
                        'subtract' => $product->stock - $quantity,
                        default => $quantity,
                    };
                    $adjustments = [[
                        'productId' => $body['productId'],
                        'newQuantity' => $newQuantity,
                        'reason' => $body['reason'] ?? '庫存調整',
                    ]];
                } elseif (!empty($body['stockCountId'])) {
                    $stockCount = StockCount::find($body['stockCountId']);
                    if (!$stockCount) {
                        throw new \Exception('NOT_FOUND:找不到盤點記錄');
                    }
                    $items = $stockCount->items ?? [];
                    foreach ($items as $item) {
                        if (($item['countedQuantity'] ?? 0) !== ($item['systemQuantity'] ?? 0)) {
                            $adjustments[] = [
                                'productId' => $item['productId'],
                                'newQuantity' => $item['countedQuantity'],
                                'reason' => "盤點調整 (盤點單: {$stockCount->id})",
                            ];
                        }
                    }
                    if (empty($adjustments)) {
                        return ['noChange' => true];
                    }
                } else {
                    throw new \Exception('BAD_REQUEST:請提供調整項目');
                }

                // Validate input
                foreach ($adjustments as $adj) {
                    if (empty($adj['productId'])) {
                        throw new \Exception('BAD_REQUEST:每個調整項目都必須包含 productId');
                    }
                    if (!isset($adj['newQuantity']) || !is_numeric($adj['newQuantity']) || $adj['newQuantity'] < 0) {
                        throw new \Exception('BAD_REQUEST:新數量必須為非負整數');
                    }
                }

                $results = [];

                // Find accounting accounts
                $inventoryAccount = ChartOfAccount::where(function ($q) {
                    $q->where('name', 'like', '%存貨%')->orWhere('code', '1400');
                })->first();

                $adjustmentGainAccount = ChartOfAccount::where(function ($q) {
                    $q->where('name', 'like', '%盤盈%')->orWhere('name', 'like', '%其他收入%');
                })->first();

                $adjustmentLossAccount = ChartOfAccount::where(function ($q) {
                    $q->where('name', 'like', '%盤虧%')
                      ->orWhere('name', 'like', '%營業費用%')
                      ->orWhere('code', '5200');
                })->first();

                $totalGainValue = 0;
                $totalLossValue = 0;
                $gainItems = [];
                $lossItems = [];

                foreach ($adjustments as $adj) {
                    $product = Product::find($adj['productId']);
                    if (!$product) {
                        throw new \Exception("NOT_FOUND:產品 {$adj['productId']} 不存在");
                    }

                    $beforeStock = $product->stock;
                    $newQuantity = (int) $adj['newQuantity'];
                    $difference = $newQuantity - $beforeStock;

                    if ($difference === 0) {
                        $results[] = [
                            'productId' => $product->id,
                            'productName' => $product->name,
                            'beforeStock' => $beforeStock,
                            'afterStock' => $newQuantity,
                            'difference' => 0,
                        ];
                        continue;
                    }

                    // Update product stock
                    DB::table('products')
                        ->where('id', $adj['productId'])
                        ->update(['stock' => $newQuantity]);

                    // Create inventory movement
                    $reasonText = $adj['reason'] ?? ($difference > 0 ? '手動入庫' : '手動出庫');
                    InventoryMovement::create([
                        'productId' => $adj['productId'],
                        'productName' => $product->name,
                        'type' => 'adjust',
                        'quantity' => abs($difference),
                        'beforeStock' => $beforeStock,
                        'afterStock' => $newQuantity,
                        'reason' => $reasonText,
                        'createdBy' => $userName,
                    ]);

                    // Calculate adjustment value for accounting
                    $costPrice = (float) ($product->costPrice ?? 0);
                    $adjustmentValue = bcmul((string) abs($difference), (string) $costPrice, 2);

                    if (bccomp($adjustmentValue, '0', 2) > 0) {
                        if ($difference > 0) {
                            $totalGainValue = bcadd((string) $totalGainValue, $adjustmentValue, 2);
                            $gainItems[] = $product->name;
                        } else {
                            $totalLossValue = bcadd((string) $totalLossValue, $adjustmentValue, 2);
                            $lossItems[] = $product->name;
                        }
                    }

                    $results[] = [
                        'productId' => $product->id,
                        'productName' => $product->name,
                        'beforeStock' => $beforeStock,
                        'afterStock' => $newQuantity,
                        'difference' => $difference,
                    ];
                }

                // Create accounting voucher for gains
                if (bccomp((string) $totalGainValue, '0', 2) > 0 && $inventoryAccount) {
                    $creditAccount = $adjustmentGainAccount ?? $inventoryAccount;
                    $gainItemsStr = implode(', ', $gainItems);
                    $reference = 'INV-ADJ-GAIN-' . now()->format('YmdHis') . '-' . substr(uniqid(), -4);

                    $voucherLines = [
                        [
                            'accountId' => $inventoryAccount->id,
                            'accountCode' => $inventoryAccount->code,
                            'accountName' => $inventoryAccount->name,
                            'debit' => (float) $totalGainValue,
                            'credit' => 0,
                            'description' => "庫存盤盈 - {$gainItemsStr}",
                        ],
                        [
                            'accountId' => $creditAccount->id,
                            'accountCode' => $creditAccount->code,
                            'accountName' => $creditAccount->name,
                            'debit' => 0,
                            'credit' => (float) $totalGainValue,
                            'description' => "庫存調整 - {$gainItemsStr}",
                        ],
                    ];

                    $voucherNumber = $this->accountingService->generateVoucherNumber('T');

                    $this->accountingService->createVoucherAndJournal([
                        'voucherNumber' => $voucherNumber,
                        'voucherType' => 'transfer',
                        'voucherDate' => now(),
                        'description' => "庫存盤盈調整 - {$gainItemsStr}",
                        'reference' => $reference,
                    ], $voucherLines, $userName);

                    $this->accountingService->updateAccountBalance($inventoryAccount->id, (float) $totalGainValue, 'increment');
                }

                // Create accounting voucher for losses
                if (bccomp((string) $totalLossValue, '0', 2) > 0 && $inventoryAccount) {
                    $debitAccount = $adjustmentLossAccount ?? $inventoryAccount;
                    $lossItemsStr = implode(', ', $lossItems);
                    $reference = 'INV-ADJ-LOSS-' . now()->format('YmdHis') . '-' . substr(uniqid(), -4);

                    $voucherLines = [
                        [
                            'accountId' => $debitAccount->id,
                            'accountCode' => $debitAccount->code,
                            'accountName' => $debitAccount->name,
                            'debit' => (float) $totalLossValue,
                            'credit' => 0,
                            'description' => "庫存盤虧 - {$lossItemsStr}",
                        ],
                        [
                            'accountId' => $inventoryAccount->id,
                            'accountCode' => $inventoryAccount->code,
                            'accountName' => $inventoryAccount->name,
                            'debit' => 0,
                            'credit' => (float) $totalLossValue,
                            'description' => "庫存調整 - {$lossItemsStr}",
                        ],
                    ];

                    $voucherNumber = $this->accountingService->generateVoucherNumber('T');

                    $this->accountingService->createVoucherAndJournal([
                        'voucherNumber' => $voucherNumber,
                        'voucherType' => 'transfer',
                        'voucherDate' => now(),
                        'description' => "庫存盤虧調整 - {$lossItemsStr}",
                        'reference' => $reference,
                    ], $voucherLines, $userName);

                    $this->accountingService->updateAccountBalance($inventoryAccount->id, (float) $totalLossValue, 'decrement');
                }

                return $results;
            });

            if (is_array($result) && isset($result['noChange'])) {
                return response()->json(['message' => '無需調整']);
            }

            return response()->json(['adjustments' => $result]);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_starts_with($message, 'NOT_FOUND:')) {
                return response()->json(['message' => str_replace('NOT_FOUND:', '', $message)], 404);
            }
            if (str_starts_with($message, 'BAD_REQUEST:')) {
                return response()->json(['message' => str_replace('BAD_REQUEST:', '', $message)], 400);
            }

            return response()->json(['message' => $message], 400);
        }
    }
}
