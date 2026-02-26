<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\ChartOfAccount;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderActionController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
    ) {}

    /**
     * Start a work order.
     */
    public function start(Request $request, string $id)
    {
        $userName = $request->user()?->name ?? '系統';

        try {
            $result = DB::transaction(function () use ($id, $userName) {
                // 1. Validate work order
                $order = WorkOrder::findOrFail($id);

                if ($order->status !== 'draft' && $order->status !== 'scheduled') {
                    throw new \Exception("CONFLICT:工單狀態不正確，目前為 {$order->status}，需要 draft 或 scheduled");
                }

                $materialUsage = [];

                // 2. Verify BOM if bomId is set
                if ($order->bomId) {
                    $bom = Bom::find($order->bomId);
                    if (!$bom) {
                        throw new \Exception('CONFLICT:找不到對應的 BOM（物料清單）');
                    }

                    $bomItems = $bom->items ?? [];
                    if (empty($bomItems)) {
                        throw new \Exception('CONFLICT:BOM 沒有材料項目');
                    }

                    // 3. Verify stock is sufficient for ALL materials first
                    foreach ($bomItems as $item) {
                        $material = Product::find($item['materialId']);
                        if (!$material) {
                            throw new \Exception("NOT_FOUND:原料 {$item['materialName']} 不存在");
                        }
                        $materialNeeded = $item['quantity'] * $order->quantity;
                        if ($material->stock < $materialNeeded) {
                            throw new \Exception("CONFLICT:原料 {$item['materialName']} 庫存不足（需要 {$materialNeeded}，目前 {$material->stock}）");
                        }
                    }

                    // 4. Deduct stock for each BOM material
                    foreach ($bomItems as $item) {
                        $material = Product::find($item['materialId']);
                        if (!$material) continue;

                        $materialNeeded = $item['quantity'] * $order->quantity;
                        $beforeStock = $material->stock;

                        DB::table('products')
                            ->where('id', $item['materialId'])
                            ->decrement('stock', $materialNeeded);

                        // 5. Create inventory movement
                        InventoryMovement::create([
                            'productId' => $item['materialId'],
                            'productName' => $item['materialName'],
                            'type' => 'out',
                            'quantity' => $materialNeeded,
                            'beforeStock' => $beforeStock,
                            'afterStock' => $beforeStock - $materialNeeded,
                            'reason' => "製造消耗 - 工單 {$order->workOrderNumber}",
                            'reference' => $id,
                            'createdBy' => $userName,
                        ]);

                        $materialUsage[] = [
                            'materialId' => $item['materialId'],
                            'plannedQuantity' => $materialNeeded,
                            'actualQuantity' => $materialNeeded,
                        ];
                    }
                }

                // 6. Update work order status
                $updateData = ['status' => 'in_progress'];
                if (!empty($materialUsage)) {
                    $updateData['materialUsage'] = $materialUsage;
                }
                $order->update($updateData);

                return $order;
            });

            return response()->json($result);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_starts_with($message, 'NOT_FOUND:')) {
                return response()->json(['message' => str_replace('NOT_FOUND:', '', $message)], 404);
            }
            if (str_starts_with($message, 'CONFLICT:')) {
                return response()->json(['message' => str_replace('CONFLICT:', '', $message)], 409);
            }

            return response()->json(['message' => $message], 400);
        }
    }

    /**
     * Complete a work order.
     */
    public function complete(Request $request, string $id)
    {
        $userName = $request->user()?->name ?? '系統';

        try {
            $result = DB::transaction(function () use ($id, $request, $userName) {
                // 1. Validate work order
                $order = WorkOrder::findOrFail($id);

                if ($order->status !== 'in_progress') {
                    throw new \Exception("CONFLICT:工單狀態不正確，目前為 {$order->status}，需要 in_progress");
                }

                if (!$order->productId) {
                    throw new \Exception('CONFLICT:工單沒有關聯產品');
                }

                $finishedQuantity = $request->input('actualQuantity', $order->quantity);

                if ($finishedQuantity <= 0) {
                    throw new \Exception('BAD_REQUEST:完工數量必須大於 0');
                }

                // 2. Add finished product to stock
                $product = Product::find($order->productId);
                if (!$product) {
                    throw new \Exception('NOT_FOUND:成品不存在');
                }

                $beforeStock = $product->stock;

                DB::table('products')
                    ->where('id', $order->productId)
                    ->increment('stock', $finishedQuantity);

                // 3. Create inventory movement for finished product
                InventoryMovement::create([
                    'productId' => $order->productId,
                    'productName' => $order->productName,
                    'type' => 'in',
                    'quantity' => $finishedQuantity,
                    'beforeStock' => $beforeStock,
                    'afterStock' => $beforeStock + $finishedQuantity,
                    'reason' => "製造完工 - 工單 {$order->workOrderNumber}",
                    'reference' => $id,
                    'createdBy' => $userName,
                ]);

                // 4. Calculate manufacturing cost
                $totalMaterialCost = '0';
                $materialUsage = $order->materialUsage;

                if ($materialUsage && count($materialUsage) > 0) {
                    foreach ($materialUsage as $usage) {
                        $material = Product::find($usage['materialId']);
                        if ($material) {
                            $cost = bcmul((string) ($material->costPrice ?? 0), (string) $usage['actualQuantity'], 2);
                            $totalMaterialCost = bcadd($totalMaterialCost, $cost, 2);
                        }
                    }
                } elseif ($order->bomId) {
                    $bom = Bom::find($order->bomId);
                    if ($bom) {
                        $bomItems = $bom->items ?? [];
                        foreach ($bomItems as $item) {
                            $material = Product::find($item['materialId']);
                            if ($material) {
                                $materialNeeded = $item['quantity'] * $order->quantity;
                                $cost = bcmul((string) ($material->costPrice ?? 0), (string) $materialNeeded, 2);
                                $totalMaterialCost = bcadd($totalMaterialCost, $cost, 2);
                            }
                        }
                    }
                }

                if (bccomp($totalMaterialCost, '0', 2) > 0) {
                    $inventoryAccount = $this->accountingService->findAccountByConditions([
                        ['name' => '存貨'], ['code' => '1400'],
                    ]);
                    $wipAccount = $this->accountingService->findAccountByConditions([
                        ['name' => '在製品'], ['name' => '在製'], ['code' => '1410'],
                    ]);

                    $sourceAccount = $wipAccount ?? $inventoryAccount;

                    if ($inventoryAccount && $sourceAccount) {
                        $voucherLines = [
                            [
                                'accountId' => $inventoryAccount->id,
                                'accountCode' => $inventoryAccount->code,
                                'accountName' => $inventoryAccount->name . '(成品)',
                                'debit' => (float) $totalMaterialCost,
                                'credit' => 0,
                                'description' => "成品入庫 - {$order->productName}",
                            ],
                            [
                                'accountId' => $sourceAccount->id,
                                'accountCode' => $sourceAccount->code,
                                'accountName' => $sourceAccount->name . '(原料)',
                                'debit' => 0,
                                'credit' => (float) $totalMaterialCost,
                                'description' => "原料轉出 - 工單 {$order->workOrderNumber}",
                            ],
                        ];

                        $voucherNumber = $this->accountingService->generateVoucherNumber('T');

                        $this->accountingService->createVoucherAndJournal([
                            'voucherNumber' => $voucherNumber,
                            'voucherType' => 'transfer',
                            'voucherDate' => now(),
                            'description' => "製造完工 - {$order->productName} - 工單 {$order->workOrderNumber}",
                            'reference' => "WO-{$order->workOrderNumber}",
                        ], $voucherLines, $userName);

                        $this->accountingService->updateAccountBalance($inventoryAccount->id, (float) $totalMaterialCost, 'increment');
                        $this->accountingService->updateAccountBalance($sourceAccount->id, (float) $totalMaterialCost, 'decrement');
                    }
                }

                // 5. Update work order status
                $order->update([
                    'status' => 'completed',
                    'completedDate' => now(),
                ]);

                return $order;
            });

            return response()->json($result);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_starts_with($message, 'NOT_FOUND:')) {
                return response()->json(['message' => str_replace('NOT_FOUND:', '', $message)], 404);
            }
            if (str_starts_with($message, 'CONFLICT:')) {
                return response()->json(['message' => str_replace('CONFLICT:', '', $message)], 409);
            }
            if (str_starts_with($message, 'BAD_REQUEST:')) {
                return response()->json(['message' => str_replace('BAD_REQUEST:', '', $message)], 400);
            }

            return response()->json(['message' => $message], 400);
        }
    }

    /**
     * Cancel a work order.
     */
    public function cancel(Request $request, string $id)
    {
        $userName = $request->user()?->name ?? '系統';

        try {
            $result = DB::transaction(function () use ($id, $userName) {
                // 1. Validate work order
                $order = WorkOrder::findOrFail($id);

                if ($order->status === 'completed') {
                    throw new \Exception('CONFLICT:已完工的工單無法取消');
                }
                if ($order->status === 'cancelled') {
                    throw new \Exception('CONFLICT:工單已經是取消狀態');
                }

                // 2. Return consumed materials if work order was in progress
                $materialUsage = $order->materialUsage;

                if ($materialUsage && count($materialUsage) > 0) {
                    foreach ($materialUsage as $usage) {
                        $material = Product::find($usage['materialId']);
                        if (!$material) continue;

                        $beforeStock = $material->stock;

                        DB::table('products')
                            ->where('id', $usage['materialId'])
                            ->increment('stock', $usage['actualQuantity']);

                        // Create inventory movement
                        InventoryMovement::create([
                            'productId' => $usage['materialId'],
                            'productName' => $material->name,
                            'type' => 'in',
                            'quantity' => $usage['actualQuantity'],
                            'beforeStock' => $beforeStock,
                            'afterStock' => $beforeStock + $usage['actualQuantity'],
                            'reason' => "工單取消退回原料 - 工單 {$order->workOrderNumber}",
                            'reference' => $id,
                            'createdBy' => $userName,
                        ]);
                    }
                }

                // 5. Update work order status
                $order->update(['status' => 'cancelled']);

                return $order;
            });

            return response()->json($result);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_starts_with($message, 'NOT_FOUND:')) {
                return response()->json(['message' => str_replace('NOT_FOUND:', '', $message)], 404);
            }
            if (str_starts_with($message, 'CONFLICT:')) {
                return response()->json(['message' => str_replace('CONFLICT:', '', $message)], 409);
            }

            return response()->json(['message' => $message], 400);
        }
    }
}
