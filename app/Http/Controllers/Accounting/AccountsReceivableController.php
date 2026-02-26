<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountsReceivable;
use Illuminate\Http\Request;

class AccountsReceivableController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountsReceivable::with('customer');

        if ($customerId = $request->input('customerId')) {
            $query->where('customerId', $customerId);
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

        $receivables = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($receivables);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customerId' => ['required', 'string'],
            'customerName' => ['required', 'string'],
            'orderId' => ['nullable', 'string'],
            'orderNumber' => ['nullable', 'string'],
            'invoiceNumber' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paidAmount' => ['nullable', 'numeric', 'min:0'],
            'dueDate' => ['required', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $receivable = AccountsReceivable::create($data);

        return response()->json($receivable, 201);
    }

    public function show(string $id)
    {
        $receivable = AccountsReceivable::findOrFail($id);

        return response()->json($receivable);
    }

    public function update(Request $request, string $id)
    {
        $receivable = AccountsReceivable::findOrFail($id);

        $data = $request->validate([
            'customerId' => ['sometimes', 'string'],
            'customerName' => ['sometimes', 'string'],
            'orderId' => ['nullable', 'string'],
            'orderNumber' => ['nullable', 'string'],
            'invoiceNumber' => ['nullable', 'string'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'paidAmount' => ['nullable', 'numeric', 'min:0'],
            'dueDate' => ['sometimes', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $receivable->fill($data)->save();

        return response()->json($receivable);
    }

    public function destroy(string $id)
    {
        $receivable = AccountsReceivable::findOrFail($id);
        $receivable->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
