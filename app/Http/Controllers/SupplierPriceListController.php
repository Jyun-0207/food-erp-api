<?php

namespace App\Http\Controllers;

use App\Models\SupplierPriceList;
use Illuminate\Http\Request;

class SupplierPriceListController extends Controller
{
    public function index(Request $request)
    {
        $priceLists = SupplierPriceList::orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($priceLists);
    }

    public function store(Request $request)
    {
        $priceList = new SupplierPriceList();
        $priceList->fill($request->only(['supplierId', 'supplierName', 'items']))->save();

        return response()->json($priceList, 201);
    }

    public function show(string $supplierPriceList)
    {
        $priceList = SupplierPriceList::where('supplierId', $supplierPriceList)->firstOrFail();

        return response()->json($priceList);
    }

    public function update(Request $request, string $supplierPriceList)
    {
        $priceList = SupplierPriceList::findOrFail($supplierPriceList);
        $priceList->fill($request->only(['supplierId', 'supplierName', 'items']))->save();

        return response()->json($priceList);
    }
}
