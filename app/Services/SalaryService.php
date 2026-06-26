<?php

namespace App\Services;

use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalaryService
{
    public function collect(array $data): SalaryPayment
    {
        return DB::transaction(function () use ($data) {
            $payment = SalaryPayment::create([
                ...$data,
                'paid_by'    => auth()->id(),
                'receipt_no' => $this->generateReceiptNo(),
                'bonus'      => $data['bonus'] ?? 0,
                'deduction'  => $data['deduction'] ?? 0,
            ]);

            return $payment->load(['teacher', 'paidBy']);
        });
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return SalaryPayment::with(['teacher', 'paidBy'])
            ->when($filters['user_id'] ?? null, fn($q, $v) => $q->where('user_id', $v))
            ->when($filters['month'] ?? null, fn($q, $v) => $q->where('month', $v))
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(min(100, (int) ($filters['per_page'] ?? 15)));
    }

    public function getDues(string $month, int $tenantId): Collection
    {
        return User::with(['teacherProfile'])
            ->whereHas('roles', fn($q) => $q->where('name', 'teacher'))
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereDoesntHave('salaryPayments', fn($q) => $q->where('month', $month))
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'user_id'     => $t->id,
                'name'        => $t->name,
                'phone'       => $t->phone,
                'base_salary' => (float) ($t->teacherProfile?->base_salary ?? 0),
                'subject'     => $t->teacherProfile?->subject,
            ]);
    }

    private function generateReceiptNo(): string
    {
        $yearMonth = now()->format('Ym');

        $last = DB::table('salary_payments')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('receipt_no', 'like', "SAL-{$yearMonth}-%")
            ->lockForUpdate()
            ->orderByDesc('receipt_no')
            ->value('receipt_no');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('SAL-%s-%04d', $yearMonth, $next);
    }
}
