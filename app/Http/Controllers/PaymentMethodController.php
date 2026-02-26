<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentMethod::with('account');

        if ($request->has('isActive')) {
            $query->where('isActive', filter_var($request->input('isActive'), FILTER_VALIDATE_BOOLEAN));
        }

        $methods = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($methods);
    }

    public function store(PaymentMethodRequest $request)
    {
        $method = new PaymentMethod();
        $method->fill($request->validated())->save();

        return response()->json($method->load('account'), 201);
    }

    public function show(string $id)
    {
        $method = PaymentMethod::with('account')->findOrFail($id);

        return response()->json($method);
    }

    public function update(PaymentMethodRequest $request, string $id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->fill($request->validated())->save();

        return response()->json($method->load('account'));
    }

    public function destroy(string $id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
