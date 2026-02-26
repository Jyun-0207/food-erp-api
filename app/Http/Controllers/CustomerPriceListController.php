<?php

namespace App\Http\Controllers;

use App\Models\CustomerPriceList;
use Illuminate\Http\Request;

class CustomerPriceListController extends Controller
{
    public function index(Request $request)
    {
        $priceLists = CustomerPriceList::orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($priceLists);
    }

    public function store(Request $request)
    {
        $priceList = new CustomerPriceList();
        $priceList->fill($request->only(['customerId', 'customerName', 'items']))->save();

        return response()->json($priceList, 201);
    }

    public function show(string $customerPriceList)
    {
        $priceList = CustomerPriceList::where('customerId', $customerPriceList)->firstOrFail();

        return response()->json($priceList);
    }

    public function update(Request $request, string $customerPriceList)
    {
        $priceList = CustomerPriceList::findOrFail($customerPriceList);
        $priceList->fill($request->only(['customerId', 'customerName', 'items']))->save();

        return response()->json($priceList);
    }
}
