<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FeeCollection;
use App\Models\SalaryPayment;

class FinanceReportService
{
    public function profitLoss(array $filters): array
    {
        $tenantId  = auth()->user()->tenant_id;
        $dateFrom  = $filters['date_from'] ?? null;
        $dateTo    = $filters['date_to']   ?? null;

        // ── Income (fee collections) ──────────────────────────────────────────
        $feeQuery = FeeCollection::where('tenant_id', $tenantId)
            ->when($dateFrom, fn($q, $v) => $q->whereDate('payment_date', '>=', $v))
            ->when($dateTo,   fn($q, $v) => $q->whereDate('payment_date', '<=', $v));

        $totalIncome = (float) (clone $feeQuery)->sum('amount_paid');

        $byFeeType = (clone $feeQuery)
            ->selectRaw('fee_type, SUM(amount_paid) as total')
            ->groupBy('fee_type')
            ->get()
            ->map(fn($r) => ['label' => $r->fee_type, 'total' => (float) $r->total]);

        // ── Salaries ──────────────────────────────────────────────────────────
        $salaryQuery = SalaryPayment::where('tenant_id', $tenantId)
            ->when($dateFrom, fn($q, $v) => $q->whereDate('payment_date', '>=', $v))
            ->when($dateTo,   fn($q, $v) => $q->whereDate('payment_date', '<=', $v));

        $totalSalary     = (float) (clone $salaryQuery)->sum('amount_paid');
        $salaryCount     = (clone $salaryQuery)->count();

        $salaryByMonth = (clone $salaryQuery)
            ->selectRaw('month, SUM(amount_paid) as total, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($r) => ['month' => $r->month, 'total' => (float) $r->total, 'count' => $r->count]);

        // ── Expenses ──────────────────────────────────────────────────────────
        $expenseQuery = Expense::where('tenant_id', $tenantId)
            ->when($dateFrom, fn($q, $v) => $q->whereDate('expense_date', '>=', $v))
            ->when($dateTo,   fn($q, $v) => $q->whereDate('expense_date', '<=', $v));

        $totalExpenses = (float) (clone $expenseQuery)->sum('amount');
        $expenseCount  = (clone $expenseQuery)->count();

        $byCategory = Expense::where('expenses.tenant_id', $tenantId)
            ->when($dateFrom, fn($q, $v) => $q->whereDate('expenses.expense_date', '>=', $v))
            ->when($dateTo,   fn($q, $v) => $q->whereDate('expenses.expense_date', '<=', $v))
            ->leftJoin('expense_categories as ec', 'expenses.expense_category_id', '=', 'ec.id')
            ->selectRaw('expenses.expense_category_id, ec.name as category_name, ec.color, SUM(expenses.amount) as total, COUNT(*) as count')
            ->groupBy('expenses.expense_category_id', 'ec.name', 'ec.color')
            ->get()
            ->map(fn($r) => [
                'category_id'   => $r->expense_category_id,
                'category_name' => $r->category_name ?? 'Uncategorized',
                'color'         => $r->color ?? '#6B7280',
                'total'         => (float) $r->total,
                'count'         => (int) $r->count,
            ])
            ->sortByDesc('total')
            ->values();

        // ── Summary ───────────────────────────────────────────────────────────
        $totalCosts = $totalSalary + $totalExpenses;
        $netProfit  = $totalIncome - $totalCosts;

        return [
            'income' => [
                'total'       => $totalIncome,
                'by_fee_type' => $byFeeType,
            ],
            'salary' => [
                'total'    => $totalSalary,
                'count'    => $salaryCount,
                'by_month' => $salaryByMonth,
            ],
            'expenses' => [
                'total'       => $totalExpenses,
                'count'       => $expenseCount,
                'by_category' => $byCategory,
            ],
            'summary' => [
                'total_income'   => $totalIncome,
                'total_salary'   => $totalSalary,
                'total_expenses' => $totalExpenses,
                'total_costs'    => $totalCosts,
                'net_profit'     => $netProfit,
                'profit_margin'  => $totalIncome > 0
                    ? round($netProfit / $totalIncome * 100, 2)
                    : null,
            ],
        ];
    }
}
