<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\FeeCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FeeService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return FeeCollection::with(['student', 'batch', 'collectedBy'])
            ->when($filters['student_id'] ?? null, fn($q, $v) => $q->where('student_id', $v))
            ->when($filters['batch_id'] ?? null, fn($q, $v) => $q->where('batch_id', $v))
            ->when($filters['fee_type'] ?? null, fn($q, $v) => $q->where('fee_type', $v))
            ->when($filters['month'] ?? null, fn($q, $v) => $q->where('month', $v))
            ->when($filters['payment_method'] ?? null, fn($q, $v) => $q->where('payment_method', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('payment_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('payment_date', '<=', $v))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->whereHas('student', fn($sq) =>
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('student_id', 'like', "%{$search}%")
                );
            })
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(min(100, (int) ($filters['per_page'] ?? 15)));
    }

    public function collect(array $data): FeeCollection
    {
        return DB::transaction(function () use ($data) {
            $fee = FeeCollection::create([
                ...$data,
                'collected_by'   => auth()->id(),
                'receipt_no'     => $this->generateReceiptNo(),
                'discount_amount'    => $data['discount_amount'] ?? 0,
                'scholarship_amount' => $data['scholarship_amount'] ?? 0,
            ]);

            return $fee->load(['student', 'batch', 'collectedBy']);
        });
    }

    public function show(FeeCollection $fee): FeeCollection
    {
        return $fee->load(['student', 'batch', 'collectedBy']);
    }

    public function getDues(string $month, int $tenantId, ?int $batchId = null): Collection
    {
        $query = DB::table('batch_students')
            ->join('students', 'students.id', '=', 'batch_students.student_id')
            ->join('batches', 'batches.id', '=', 'batch_students.batch_id')
            ->leftJoin('fee_collections', function ($join) use ($month) {
                $join->on('fee_collections.student_id', '=', 'batch_students.student_id')
                     ->on('fee_collections.batch_id', '=', 'batch_students.batch_id')
                     ->where('fee_collections.fee_type', 'monthly')
                     ->where('fee_collections.month', $month)
                     ->whereNull('fee_collections.deleted_at');
            })
            ->where('batches.tenant_id', $tenantId)
            ->where('batches.status', 'active')
            ->where('students.status', 'active')
            ->whereNull('students.deleted_at')
            ->where(function ($q) {
                $q->whereNull('fee_collections.id')
                  ->orWhereRaw('(fee_collections.amount_due - fee_collections.discount_amount - fee_collections.scholarship_amount) > fee_collections.amount_paid');
            })
            ->when($batchId, fn($q) => $q->where('batches.id', $batchId))
            ->select([
                'students.id as student_db_id',
                'students.student_id',
                'students.name',
                'students.phone',
                'batches.id as batch_id',
                'batches.name as batch_name',
                'batches.fee_amount',
                'fee_collections.id as fee_id',
                'fee_collections.receipt_no',
                'fee_collections.amount_due',
                'fee_collections.discount_amount',
                'fee_collections.scholarship_amount',
                'fee_collections.amount_paid',
            ])
            ->orderBy('students.name')
            ->get();

        return $query->map(function ($row) {
            $amountDue    = round((float) ($row->amount_due ?? $row->fee_amount), 2);
            $discount     = round((float) ($row->discount_amount ?? 0), 2);
            $scholarship  = round((float) ($row->scholarship_amount ?? 0), 2);
            $paid         = round((float) ($row->amount_paid ?? 0), 2);
            $net          = round(max(0, $amountDue - $discount - $scholarship), 2);
            $balance      = round(max(0, $net - $paid), 2);

            return [
                'student_db_id' => $row->student_db_id,
                'student_id'    => $row->student_id,
                'name'          => $row->name,
                'phone'         => $row->phone,
                'batch_id'      => $row->batch_id,
                'batch_name'    => $row->batch_name,
                'fee_amount'    => (float) $row->fee_amount,
                'amount_due'    => $amountDue,
                'discount'      => $discount,
                'scholarship'   => $scholarship,
                'amount_paid'   => $paid,
                'balance'       => $balance,
                'partially_paid' => $paid > 0 && $balance > 0,
                'fee_id'        => $row->fee_id,
            ];
        });
    }

    public function getSummary(int $tenantId): array
    {
        $today     = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd   = now()->endOfMonth()->toDateString();

        $todayTotal = FeeCollection::where('tenant_id', $tenantId)
            ->whereDate('payment_date', $today)
            ->sum('amount_paid');

        $monthTotal = FeeCollection::where('tenant_id', $tenantId)
            ->whereDate('payment_date', '>=', $monthStart)
            ->whereDate('payment_date', '<=', $monthEnd)
            ->sum('amount_paid');

        return [
            'today_collection' => (float) $todayTotal,
            'month_collection' => (float) $monthTotal,
        ];
    }

    private function generateReceiptNo(): string
    {
        $yearMonth = now()->format('Ym');

        $last = DB::table('fee_collections')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('receipt_no', 'like', "RCP-{$yearMonth}-%")
            ->lockForUpdate()
            ->orderByDesc('receipt_no')
            ->value('receipt_no');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('RCP-%s-%04d', $yearMonth, $next);
    }
}
