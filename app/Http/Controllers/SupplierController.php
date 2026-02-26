<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('isActive')) {
            $query->where('isActive', filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN));
        }

        $suppliers = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($suppliers);
    }

    public function store(SupplierRequest $request)
    {
        $supplier = new Supplier();
        $supplier->fill($request->validated())->save();

        return response()->json($supplier, 201);
    }

    public function show(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        return response()->json($supplier);
    }

    public function update(SupplierRequest $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->fill($request->validated())->save();

        return response()->json($supplier);
    }

    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->purchaseOrders()->count() > 0) {
            return response()->json(['message' => '此供應商有相關訂單，無法刪除'], 409);
        }

        $supplier->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
