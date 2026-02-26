<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('isActive')) {
            $query->where('isActive', filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN));
        }

        $customers = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($customers);
    }

    public function store(CustomerRequest $request)
    {
        $customer = new Customer();
        $customer->fill($request->validated())->save();

        return response()->json($customer, 201);
    }

    public function show(string $id)
    {
        $customer = Customer::findOrFail($id);

        return response()->json($customer);
    }

    public function update(CustomerRequest $request, string $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->fill($request->validated())->save();

        return response()->json($customer);
    }

    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);

        if ($customer->salesOrders()->count() > 0) {
            return response()->json(['message' => '此客戶有相關訂單，無法刪除'], 409);
        }

        $customer->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
