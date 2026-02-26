<?php

namespace App\Services;

use App\Models\AccountingVoucher;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Generate voucher number with format {prefix}-{YYYYMMDD}-{seq}.
     */
    public function generateVoucherNumber(string $prefix = 'V'): string
    {
        $dateStr = now()->format('Ymd');
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $count = DB::table('accounting_vouchers')
            ->where('voucherNumber', 'like', "{$prefix}-{$dateStr}%")
            ->whereBetween('createdAt', [$startOfDay, $endOfDay])
            ->lockForUpdate()
            ->count();

        $seq = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$dateStr}-{$seq}";
    }

    /**
     * Generate order number for sales_orders or purchase_orders.
     * Format: {prefix}{YYYYMMDD}{4-digit seq}
     */
    public function generateOrderNumber(string $model, string $prefix): string
    {
        $dateStr = now()->format('Ymd');
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        $count = DB::table($model)
            ->where('orderNumber', 'like', "{$prefix}{$dateStr}%")
            ->whereBetween('createdAt', [$startOfDay, $endOfDay])
            ->lockForUpdate()
            ->count();

        $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$dateStr}{$seq}";
    }

    /**
     * Helper to find ChartOfAccount by name containing.
     */
    public function findAccount(string $nameContains): ?object
    {
        return ChartOfAccount::where('name', 'like', "%{$nameContains}%")->first();
    }

    /**
     * Find account by OR conditions on name or code.
     * Each condition is ['name' => 'contains_value'] or ['code' => 'exact_value'] or ['type' => 'exact_value'].
     */
    public function findAccountByConditions(array $conditions): ?object
    {
        return ChartOfAccount::where(function ($query) use ($conditions) {
            foreach ($conditions as $condition) {
                $query->orWhere(function ($q) use ($condition) {
                    foreach ($condition as $field => $value) {
                        if ($field === 'name') {
                            $q->where('name', 'like', "%{$value}%");
                        } else {
                            $q->where($field, $value);
                        }
                    }
                });
            }
        })->first();
    }

    /**
     * Create both AccountingVoucher and JournalEntry from the same lines data.
     */
    public function createVoucherAndJournal(array $voucherData, array $lines, string $createdBy): void
    {
        $totalDebit = array_reduce($lines, fn($sum, $l) => bcadd($sum, (string) ($l['debit'] ?? 0), 2), '0');
        $totalCredit = array_reduce($lines, fn($sum, $l) => bcadd($sum, (string) ($l['credit'] ?? 0), 2), '0');

        AccountingVoucher::create(array_merge($voucherData, [
            'lines' => $lines,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'status' => $voucherData['status'] ?? 'approved',
            'preparedBy' => $voucherData['preparedBy'] ?? $createdBy,
            'preparedAt' => $voucherData['preparedAt'] ?? now(),
            'approvedBy' => $voucherData['approvedBy'] ?? $createdBy,
            'approvedAt' => $voucherData['approvedAt'] ?? now(),
        ]));

        JournalEntry::create([
            'date' => $voucherData['voucherDate'] ?? now(),
            'description' => $voucherData['description'] ?? '',
            'entries' => array_map(fn($line) => [
                'accountId' => $line['accountId'],
                'accountName' => $line['accountName'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
            ], $lines),
            'reference' => $voucherData['reference'] ?? '',
            'createdBy' => $createdBy,
        ]);
    }

    /**
     * Atomically update ChartOfAccount balance.
     */
    public function updateAccountBalance(string $accountId, float $amount, string $operation = 'increment'): void
    {
        if ($operation === 'increment') {
            DB::table('chart_of_accounts')
                ->where('id', $accountId)
                ->increment('balance', $amount);
        } else {
            DB::table('chart_of_accounts')
                ->where('id', $accountId)
                ->decrement('balance', $amount);
        }
    }
}
