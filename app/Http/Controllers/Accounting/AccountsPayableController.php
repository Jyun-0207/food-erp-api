<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountsPayable;
use Illuminate\Http\Request;

class AccountsPayableController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountsPayable::with('supplier');

        if ($supplierId = $request->input('supplierId')) {
            $query->where('supplierId', $supplierId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($startDate = $request->input('startDate')) {
            $query->where('dueDate', '>=', $startDate);
        }

        if ($endDate = $request->input('endDate')) {
            $query->where('dueDate', '<=', $endDate);
        }

        $payables = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($payables);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplierId' => ['required', 'string'],
            'supplierName' => ['required', 'string'],
            'orderId' => ['nullable', 'string'],
            'orderNumber' => ['nullable', 'string'],
            'invoiceNumber' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paidAmount' => ['nullable', 'numeric', 'min:0'],
            'dueDate' => ['required', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $payable = AccountsPayable::create($data);

        return response()->json($payable, 201);
    }

    public function show(string $id)
    {
        $payable = AccountsPayable::findOrFail($id);

        return response()->json($payable);
    }

    public function update(Request $request, string $id)
    {
        $payable = AccountsPayable::findOrFail($id);

        $data = $request->validate([
            'supplierId' => ['sometimes', 'string'],
            'supplierName' => ['sometimes', 'string'],
            'orderId' => ['nullable', 'string'],
            'orderNumber' => ['nullable', 'string'],
            'invoiceNumber' => ['nullable', 'string'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'paidAmount' => ['nullable', 'numeric', 'min:0'],
            'dueDate' => ['sometimes', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $payable->fill($data)->save();

        return response()->json($payable);
    }

    public function destroy(string $id)
    {
        $payable = AccountsPayable::findOrFail($id);
        $payable->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
