<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('categoryId')) {
            $query->where('categoryId', $request->input('categoryId'));
        }

        if ($request->has('isActive')) {
            $query->where('isActive', filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN));
        }

        $products = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        // Strip costPrice for unauthenticated users
        if (!auth()->guard('sanctum')->user()) {
            $products->getCollection()->transform(function ($product) {
                $product->makeHidden('costPrice');
                return $product;
            });
        }

        return response()->json($products);
    }

    public function store(ProductRequest $request)
    {
        $product = new Product();
        $product->fill($request->validated())->save();

        return response()->json($product->load('category'), 201);
    }

    public function show(string $id, Request $request)
    {
        $product = Product::with(['category', 'batches'])->findOrFail($id);

        if (!auth()->guard('sanctum')->user()) {
            $product->makeHidden('costPrice');
        }

        return response()->json($product);
    }

    public function update(ProductRequest $request, string $id)
    {
        $product = Product::findOrFail($id);
        $product->fill($request->validated())->save();

        return response()->json($product->load('category'));
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // Check referential integrity
        $batchCount = $product->batches()->count();
        if ($batchCount > 0) {
            return response()->json(['message' => '此產品有相關批次記錄，無法刪除'], 409);
        }

        $movementCount = \App\Models\InventoryMovement::where('productId', $id)->count();
        if ($movementCount > 0) {
            return response()->json(['message' => '此產品有庫存異動記錄，無法刪除'], 409);
        }

        $product->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
