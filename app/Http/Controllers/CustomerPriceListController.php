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
        $priceList = CustomerPriceList::updateOrCreate(
            ['customerId' => $request->input('customerId')],
            [
                'customerName' => $request->input('customerName'),
                'items' => $request->input('items'),
            ]
        );

        return response()->json($priceList, 201);
    }

    public function show(string $customerPriceList)
    {
        $priceList = CustomerPriceList::where('customerId', $customerPriceList)->first();

        return response()->json($priceList); // null if not found
    }

    public function update(Request $request, string $customerPriceList)
    {
        $priceList = CustomerPriceList::updateOrCreate(
            ['customerId' => $request->input('customerId', $customerPriceList)],
            [
                'customerName' => $request->input('customerName'),
                'items' => $request->input('items'),
            ]
        );

        return response()->json($priceList);
    }
}
