<?php

namespace App\Services\Fin;

use App\Models\FinAccount;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

final class FinReportService
{
    /**
     * Mizan (Trial Balance) — belirli bir tarihe kadar tüm posted hareketler.
     */
    public function trialBalance(string $asOfDate): array
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) return [];

        $rows = DB::table('fin_account as a')
            ->leftJoin('fin_journal_entry_line as l', 'a.id', '=', 'l.account_id')
            ->leftJoin('fin_journal_entry as e', function ($join) use ($asOfDate) {
                $join->on('l.journal_entry_id', '=', 'e.id')
                    ->where('e.status', '=', 'posted')
                    ->where('e.entry_date', '<=', $asOfDate);
            })
            ->where('a.tenant_id', $tenantId)
            ->select('a.id', 'a.code', 'a.name', 'a.type', 'a.is_postable',
                DB::raw('COALESCE(SUM(CASE WHEN e.id IS NOT NULL THEN l.debit ELSE 0 END), 0) as total_debit'),
                DB::raw('COALESCE(SUM(CASE WHEN e.id IS NOT NULL THEN l.credit ELSE 0 END), 0) as total_credit'))
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.is_postable')
            ->orderBy('a.code')
            ->get();

        $result = [];
        foreach ($rows as $r) {
            $debit = (float) $r->total_debit;
            $credit = (float) $r->total_credit;
            if ($debit == 0 && $credit == 0) continue;

            $balance = in_array($r->type, ['asset', 'expense'], true)
                ? $debit - $credit
                : $credit - $debit;

            $result[] = [
                'id'          => $r->id,
                'code'        => $r->code,
                'name'        => $r->name,
                'type'        => $r->type,
                'is_postable' => (bool) $r->is_postable,
                'debit'       => $debit,
                'credit'      => $credit,
                'balance'     => $balance,
            ];
        }
        return $result;
    }

    /**
     * Bilanço (Balance Sheet) — Varlık = Yükümlülük + Özkaynak (+ Net Kâr)
     */
    public function balanceSheet(string $asOfDate): array
    {
        $tb = collect($this->trialBalance($asOfDate));

        $assets      = $tb->where('type', 'asset')->values()->all();
        $liabilities = $tb->where('type', 'liability')->values()->all();
        $equity      = $tb->where('type', 'equity')->values()->all();

        $totalAssets      = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity      = array_sum(array_column($equity, 'balance'));

        $revenue   = $tb->where('type', 'revenue')->sum('balance');
        $expense   = $tb->where('type', 'expense')->sum('balance');
        $netProfit = $revenue - $expense;

        $totalEquityIncl = $totalEquity + $netProfit;

        return [
            'assets'            => $assets,
            'liabilities'       => $liabilities,
            'equity'            => $equity,
            'revenue_total'     => $revenue,
            'expense_total'     => $expense,
            'net_profit'        => $netProfit,
            'total_assets'      => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity'      => $totalEquityIncl,
            'balanced'          => abs($totalAssets - ($totalLiabilities + $totalEquityIncl)) < 0.01,
            'as_of'             => $asOfDate,
        ];
    }

    /**
     * Gelir Tablosu (Income Statement) — Belirli aralıktaki gelir/gider.
     */
    public function incomeStatement(string $fromDate, string $toDate): array
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) return [];

        $rows = DB::table('fin_account as a')
            ->leftJoin('fin_journal_entry_line as l', 'a.id', '=', 'l.account_id')
            ->leftJoin('fin_journal_entry as e', function ($join) use ($fromDate, $toDate) {
                $join->on('l.journal_entry_id', '=', 'e.id')
                    ->where('e.status', '=', 'posted')
                    ->whereBetween('e.entry_date', [$fromDate, $toDate]);
            })
            ->where('a.tenant_id', $tenantId)
            ->whereIn('a.type', ['revenue', 'expense'])
            ->select('a.id', 'a.code', 'a.name', 'a.type',
                DB::raw('COALESCE(SUM(CASE WHEN e.id IS NOT NULL THEN l.debit ELSE 0 END), 0) as total_debit'),
                DB::raw('COALESCE(SUM(CASE WHEN e.id IS NOT NULL THEN l.credit ELSE 0 END), 0) as total_credit'))
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get();

        $revenues = [];
        $expenses = [];
        $totalRevenue = 0;
        $totalExpense = 0;

        foreach ($rows as $r) {
            $debit = (float) $r->total_debit;
            $credit = (float) $r->total_credit;
            if ($debit == 0 && $credit == 0) continue;

            if ($r->type === 'revenue') {
                $amount = $credit - $debit;
                $totalRevenue += $amount;
                $revenues[] = ['code' => $r->code, 'name' => $r->name, 'amount' => $amount];
            } else {
                $amount = $debit - $credit;
                $totalExpense += $amount;
                $expenses[] = ['code' => $r->code, 'name' => $r->name, 'amount' => $amount];
            }
        }

        return [
            'revenues'      => $revenues,
            'expenses'      => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_profit'    => $totalRevenue - $totalExpense,
            'from_date'     => $fromDate,
            'to_date'       => $toDate,
        ];
    }

    /**
     * KDV Beyannamesi — Hesaplanan KDV (391) - İndirilecek KDV (191)
     */
    public function vatReturn(string $fromDate, string $toDate): array
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) return ['error' => 'Tenant yok.'];

        $output = DB::table('fin_journal_entry_line as l')
            ->join('fin_account as a', 'l.account_id', '=', 'a.id')
            ->join('fin_journal_entry as e', 'l.journal_entry_id', '=', 'e.id')
            ->where('a.tenant_id', $tenantId)
            ->where('a.code', '391')
            ->where('e.status', 'posted')
            ->whereBetween('e.entry_date', [$fromDate, $toDate])
            ->selectRaw('COALESCE(SUM(l.credit), 0) - COALESCE(SUM(l.debit), 0) as amount')
            ->value('amount');

        $input = DB::table('fin_journal_entry_line as l')
            ->join('fin_account as a', 'l.account_id', '=', 'a.id')
            ->join('fin_journal_entry as e', 'l.journal_entry_id', '=', 'e.id')
            ->where('a.tenant_id', $tenantId)
            ->where('a.code', '191')
            ->where('e.status', 'posted')
            ->whereBetween('e.entry_date', [$fromDate, $toDate])
            ->selectRaw('COALESCE(SUM(l.debit), 0) - COALESCE(SUM(l.credit), 0) as amount')
            ->value('amount');

        $outputAmt = (float) $output;
        $inputAmt  = (float) $input;

        return [
            'output_vat' => $outputAmt,
            'input_vat'  => $inputAmt,
            'payable'    => $outputAmt - $inputAmt,
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
        ];
    }
}
