<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingVoucher;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class AccountingVoucherController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
    ) {}

    public function index(Request $request)
    {
        $query = AccountingVoucher::query();

        if ($voucherType = $request->input('voucherType')) {
            $query->where('voucherType', $voucherType);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($startDate = $request->input('startDate')) {
            $query->where('voucherDate', '>=', $startDate);
        }

        if ($endDate = $request->input('endDate')) {
            $query->where('voucherDate', '<=', $endDate);
        }

        $vouchers = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($vouchers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'voucherNumber' => ['nullable', 'string'],
            'voucherType' => ['required', 'string'],
            'voucherDate' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'totalDebit' => ['required', 'numeric'],
            'totalCredit' => ['required', 'numeric'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'reference' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'preparedBy' => ['nullable', 'string'],
            'reviewedBy' => ['nullable', 'string'],
            'approvedBy' => ['nullable', 'string'],
        ]);

        if (empty($data['voucherNumber'])) {
            $prefix = match ($data['voucherType'] ?? 'transfer') {
                'receipt' => 'R',
                'payment' => 'P',
                default => 'T',
            };
            $data['voucherNumber'] = $this->accountingService->generateVoucherNumber($prefix);
        }

        $data['preparedAt'] = now();

        $voucher = AccountingVoucher::create($data);

        return response()->json($voucher, 201);
    }

    public function show(string $id)
    {
        $voucher = AccountingVoucher::findOrFail($id);

        return response()->json($voucher);
    }

    public function update(Request $request, string $id)
    {
        $voucher = AccountingVoucher::findOrFail($id);

        $data = $request->validate([
            'voucherNumber' => ['sometimes', 'string'],
            'voucherType' => ['sometimes', 'string'],
            'voucherDate' => ['sometimes', 'date'],
            'lines' => ['sometimes', 'array', 'min:1'],
            'totalDebit' => ['sometimes', 'numeric'],
            'totalCredit' => ['sometimes', 'numeric'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'reference' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'preparedBy' => ['nullable', 'string'],
            'reviewedBy' => ['nullable', 'string'],
            'reviewedAt' => ['nullable', 'date'],
            'approvedBy' => ['nullable', 'string'],
            'approvedAt' => ['nullable', 'date'],
            'rejectedReason' => ['nullable', 'string'],
            'voidedBy' => ['nullable', 'string'],
            'voidedAt' => ['nullable', 'date'],
            'voidedReason' => ['nullable', 'string'],
        ]);

        $voucher->fill($data)->save();

        return response()->json($voucher);
    }

    public function destroy(string $id)
    {
        $voucher = AccountingVoucher::findOrFail($id);
        $voucher->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
