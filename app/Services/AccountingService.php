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
     * Format: {resolved prefix}{digits-digit seq}, e.g. SO2026072801.
     *
     * The prefix is a strftime-style template ('SO%Y%m%d'), which is what the
     * 流水號設定 UI documents and stores. It used to be treated as a literal
     * and have the date appended on top, producing SO%Y%m%d2026072801 — and
     * the raw '%' also acted as a LIKE wildcard in the sequence lookup.
     *
     * @param string|null $date  Date string (Y-m-d) to use for numbering; defaults to today.
     */
    public function generateOrderNumber(string $model, string $prefix, int $digits = 4, ?string $date = null): string
    {
        $carbon = $date ? \Carbon\Carbon::parse($date) : now();

        $base = $this->parseNumberTemplate($prefix, $carbon);
        // A plain prefix with no date codes (e.g. the storefront's 'SO') still
        // gets the date, so its numbers keep their existing shape.
        if ($base === $prefix) {
            $base .= $carbon->format('Ymd');
        }

        $count = DB::table($model)
            ->where('orderNumber', 'like', $base . '%')
            ->lockForUpdate()
            ->count();

        $seq = str_pad((string) ($count + 1), $digits, '0', STR_PAD_LEFT);

        return "{$base}{$seq}";
    }

    /**
     * Resolve the %Y/%m/%d-style codes the frontend writes into number
     * templates. Mirrors parseOrderNumberTemplate() in the Next app.
     */
    private function parseNumberTemplate(string $template, \Carbon\Carbon $d): string
    {
        return strtr($template, [
            '%Y' => $d->format('Y'),
            '%y' => $d->format('y'),
            '%m' => $d->format('m'),
            '%d' => $d->format('d'),
            '%H' => $d->format('H'),
            '%M' => $d->format('i'),
            '%S' => $d->format('s'),
        ]);
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
