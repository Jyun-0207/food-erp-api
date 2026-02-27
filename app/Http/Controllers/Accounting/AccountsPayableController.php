<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountsPayable;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountsPayableController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
    ) {}

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

        $previousPaidAmount = (float) $payable->paidAmount;
        $newPaidAmount = isset($data['paidAmount']) ? (float) $data['paidAmount'] : $previousPaidAmount;
        $newPayment = bcsub((string) $newPaidAmount, (string) $previousPaidAmount, 2);

        DB::transaction(function () use ($payable, $data, $newPayment, $request) {
            $payable->fill($data)->save();

            // Generate payment journal entry when paidAmount increases
            if (bccomp($newPayment, '0', 2) > 0) {
                $cashAccount = $this->accountingService->findAccountByConditions([
                    ['name' => '現金'], ['name' => '銀行'],
                ]);
                $payableAccount = $this->accountingService->findAccount('應付');

                if ($cashAccount && $payableAccount) {
                    $userName = $request->user()?->name ?? '系統';
                    $description = "付款 - {$payable->supplierName}" .
                        ($payable->orderNumber ? " - 採購單 {$payable->orderNumber}" : '');

                    $voucherLines = [
                        [
                            'accountId' => $payableAccount->id,
                            'accountCode' => $payableAccount->code,
                            'accountName' => $payableAccount->name,
                            'debit' => (float) $newPayment,
                            'credit' => 0,
                            'description' => $description,
                        ],
                        [
                            'accountId' => $cashAccount->id,
                            'accountCode' => $cashAccount->code,
                            'accountName' => $cashAccount->name,
                            'debit' => 0,
                            'credit' => (float) $newPayment,
                            'description' => $description,
                        ],
                    ];

                    $voucherNumber = $this->accountingService->generateVoucherNumber('P');

                    $this->accountingService->createVoucherAndJournal([
                        'voucherNumber' => $voucherNumber,
                        'voucherType' => 'payment',
                        'voucherDate' => now(),
                        'description' => $description,
                        'reference' => $payable->orderNumber
                            ? "PO-PAY-{$payable->orderNumber}"
                            : "AP-PAY-{$payable->id}",
                    ], $voucherLines, $userName);

                    $this->accountingService->updateAccountBalance($payableAccount->id, (float) $newPayment, 'decrement');
                    $this->accountingService->updateAccountBalance($cashAccount->id, (float) $newPayment, 'decrement');
                }
            }
        });

        return response()->json($payable->fresh());
    }

    public function destroy(string $id)
    {
        $payable = AccountsPayable::findOrFail($id);
        $payable->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
