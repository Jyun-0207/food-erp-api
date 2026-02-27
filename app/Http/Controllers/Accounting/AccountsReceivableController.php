<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountsReceivable;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountsReceivableController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
    ) {}

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

        $previousPaidAmount = (float) $receivable->paidAmount;
        $newPaidAmount = isset($data['paidAmount']) ? (float) $data['paidAmount'] : $previousPaidAmount;
        $newPayment = bcsub((string) $newPaidAmount, (string) $previousPaidAmount, 2);

        DB::transaction(function () use ($receivable, $data, $newPayment, $request) {
            $receivable->fill($data)->save();

            // Generate receipt journal entry when paidAmount increases
            if (bccomp($newPayment, '0', 2) > 0) {
                $cashAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '現金'], ['name' => '銀行'],
                ]);
                $receivableAccount = $this->accountingService->findAccount('應收');

                if ($cashAccount && $receivableAccount) {
                    $userName = $request->user()?->name ?? '系統';
                    $description = "收款 - {$receivable->customerName}" .
                        ($receivable->orderNumber ? " - 訂單 {$receivable->orderNumber}" : '');

                    $voucherLines = [
                        [
                            'accountId' => $cashAccount->id,
                            'accountCode' => $cashAccount->code,
                            'accountName' => $cashAccount->name,
                            'debit' => (float) $newPayment,
                            'credit' => 0,
                            'description' => $description,
                        ],
                        [
                            'accountId' => $receivableAccount->id,
                            'accountCode' => $receivableAccount->code,
                            'accountName' => $receivableAccount->name,
                            'debit' => 0,
                            'credit' => (float) $newPayment,
                            'description' => $description,
                        ],
                    ];

                    $voucherNumber = $this->accountingService->generateVoucherNumber('R');

                    $this->accountingService->createVoucherAndJournal([
                        'voucherNumber' => $voucherNumber,
                        'voucherType' => 'receipt',
                        'voucherDate' => now(),
                        'description' => $description,
                        'reference' => $receivable->orderNumber
                            ? "SO-PAY-{$receivable->orderNumber}"
                            : "AR-PAY-{$receivable->id}",
                    ], $voucherLines, $userName);

                    $this->accountingService->updateAccountBalance($cashAccount->id, (float) $newPayment, 'increment');
                    $this->accountingService->updateAccountBalance($receivableAccount->id, (float) $newPayment, 'decrement');
                }
            }
        });

        return response()->json($receivable->fresh());
    }

    public function destroy(string $id)
    {
        $receivable = AccountsReceivable::findOrFail($id);
        $receivable->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
