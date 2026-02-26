<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryMovement::query();

        if ($productId = $request->input('productId')) {
            $query->where('productId', $productId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($startDate = $request->input('startDate')) {
            $query->where('createdAt', '>=', $startDate);
        }

        if ($endDate = $request->input('endDate')) {
            $query->where('createdAt', '<=', $endDate);
        }

        $movements = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($movements);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'productId' => ['nullable', 'string'],
            'productName' => ['required', 'string'],
            'type' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
            'createdBy' => ['nullable', 'string'],
        ]);

        $movement = DB::transaction(function () use ($data) {
            // Get current stock
            $product = $data['productId']
                ? Product::find($data['productId'])
                : null;
            $beforeStock = $product->stock ?? 0;

            // Calculate stock change: 'in' and 'adjust' add, 'out' subtracts
            $stockChange = in_array($data['type'], ['in', 'adjust'])
                ? abs($data['quantity'])
                : -abs($data['quantity']);
            $afterStock = $beforeStock + $stockChange;

            $movement = InventoryMovement::create([
                'productId' => $data['productId'] ?? null,
                'productName' => $data['productName'],
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'beforeStock' => $beforeStock,
                'afterStock' => $afterStock,
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'createdBy' => $data['createdBy'] ?? null,
            ]);

            // Update product stock
            if ($data['productId'] && $product) {
                DB::table('products')
                    ->where('id', $data['productId'])
                    ->increment('stock', $stockChange);
            }

            return $movement;
        });

        return response()->json($movement, 201);
    }

    public function show(string $id)
    {
        $movement = InventoryMovement::findOrFail($id);

        return response()->json($movement);
    }

    public function update(Request $request, string $id)
    {
        $movement = InventoryMovement::findOrFail($id);

        $data = $request->validate([
            'reason' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
        ]);

        $movement->fill($data)->save();

        return response()->json($movement);
    }

    public function destroy(string $id)
    {
        $movement = InventoryMovement::findOrFail($id);
        $movement->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
