<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return Expense::with(['category:id,name,color', 'branch:id,name', 'recordedBy:id,name'])
            ->when($filters['category_id'] ?? null, fn($q, $v) => $q->where('expense_category_id', $v))
            ->when($filters['branch_id']   ?? null, fn($q, $v) => $q->where('branch_id', $v))
            ->when($filters['date_from']   ?? null, fn($q, $v) => $q->whereDate('expense_date', '>=', $v))
            ->when($filters['date_to']     ?? null, fn($q, $v) => $q->whereDate('expense_date', '<=', $v))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(min(100, (int) ($filters['per_page'] ?? 20)));
    }

    public function create(array $data): Expense
    {
        $expense = Expense::create([
            ...$data,
            'tenant_id'   => auth()->user()->tenant_id,
            'recorded_by' => auth()->id(),
        ]);

        return $expense->load(['category:id,name,color', 'branch:id,name', 'recordedBy:id,name']);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);
        return $expense->fresh(['category:id,name,color', 'branch:id,name']);
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }
}
