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
        return SalaryPayment::with(['teacher.roles', 'paidBy'])
            ->when($filters['user_id'] ?? null, fn($q, $v) => $q->where('user_id', $v))
            ->when($filters['month'] ?? null, fn($q, $v) => $q->where('month', $v))
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(min(100, (int) ($filters['per_page'] ?? 15)));
    }

    // All roles that are eligible for payroll (excludes owner / super-admin)
    public const PAYROLL_ROLES = ['teacher', 'manager', 'accountant', 'receptionist'];

    public function monthlyStatus(string $month, int $tenantId, ?int $branchId = null): Collection
    {
        $employees = User::with(['teacherProfile', 'branch', 'roles'])
            ->whereHas('roles', fn($q) => $q->whereIn('name', self::PAYROLL_ROLES))
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($branchId, fn($q, $v) => $q->where('branch_id', $v))
            ->orderBy('name')
            ->get();

        $payments = SalaryPayment::where('tenant_id', $tenantId)
            ->where('month', $month)
            ->whereIn('user_id', $employees->pluck('id'))
            ->get()
            ->groupBy('user_id');

        return $employees->map(function ($employee) use ($payments) {
            $baseSalary        = $employee->baseSalary();
            $employeePayments  = $payments->get($employee->id, collect());
            $totalPaid         = (float) $employeePayments->sum('amount_paid');
            $remaining         = max(0, $baseSalary - $totalPaid);
            $primaryRole       = $employee->roles->first()?->name ?? 'staff';

            $status = match (true) {
                $totalPaid <= 0  => 'unpaid',
                $remaining <= 0  => 'paid',
                default          => 'partial',
            };

            return [
                'user_id'        => $employee->id,
                'name'           => $employee->name,
                'phone'          => $employee->phone,
                'position'       => ucfirst($primaryRole),
                'branch_id'      => $employee->branch_id,
                'branch_name'    => $employee->branch?->name,
                'base_salary'    => $baseSalary,
                'total_paid'     => $totalPaid,
                'remaining'      => $remaining,
                'status'         => $status,
                'last_paid_date' => $employeePayments->sortByDesc('payment_date')->first()?->payment_date?->toDateString(),
            ];
        });
    }

    public function getDues(string $month, int $tenantId): Collection
    {
        return User::with(['teacherProfile'])
            ->whereHas('roles', fn($q) => $q->whereIn('name', self::PAYROLL_ROLES))
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
                'base_salary' => $t->baseSalary(),
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
