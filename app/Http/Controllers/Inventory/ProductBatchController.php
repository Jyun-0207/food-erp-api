<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductBatch::with('product');

        if ($productId = $request->input('productId')) {
            $query->where('productId', $productId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($supplierId = $request->input('supplierId')) {
            $query->where('supplierId', $supplierId);
        }

        $batches = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($batches);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'productId' => ['required', 'string'],
            'productName' => ['required', 'string'],
            'batchNumber' => ['nullable', 'string'],
            'manufacturingDate' => ['nullable', 'date'],
            'expirationDate' => ['nullable', 'date'],
            'receivedDate' => ['nullable', 'date'],
            'initialQuantity' => ['required', 'integer', 'min:0'],
            'currentQuantity' => ['required', 'integer', 'min:0'],
            'reservedQuantity' => ['nullable', 'integer', 'min:0'],
            'supplierId' => ['nullable', 'string'],
            'supplierName' => ['nullable', 'string'],
            'purchaseOrderId' => ['nullable', 'string'],
            'purchaseOrderNumber' => ['nullable', 'string'],
            'costPrice' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        // Auto-generate batchNumber if not provided
        if (empty($data['batchNumber'])) {
            $dateStr = now()->format('Ymd');
            $count = DB::table('product_batches')
                ->where('batchNumber', 'like', "BN{$dateStr}%")
                ->count();
            $data['batchNumber'] = 'BN' . $dateStr . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        }

        $batch = ProductBatch::create($data);

        return response()->json($batch->load('product'), 201);
    }

    public function show(string $id)
    {
        $batch = ProductBatch::with('product')->findOrFail($id);

        return response()->json($batch);
    }

    public function update(Request $request, string $id)
    {
        $batch = ProductBatch::findOrFail($id);

        $data = $request->validate([
            'batchNumber' => ['sometimes', 'string'],
            'manufacturingDate' => ['nullable', 'date'],
            'expirationDate' => ['nullable', 'date'],
            'receivedDate' => ['nullable', 'date'],
            'initialQuantity' => ['sometimes', 'integer', 'min:0'],
            'currentQuantity' => ['sometimes', 'integer', 'min:0'],
            'reservedQuantity' => ['nullable', 'integer', 'min:0'],
            'costPrice' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $batch->fill($data)->save();

        return response()->json($batch);
    }

    public function destroy(string $id)
    {
        $batch = ProductBatch::findOrFail($id);
        $batch->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
