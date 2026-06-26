<?php

namespace App\Services;

use App\Models\FeeCollection;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function collectionReport(array $filters): array
    {
        $perPage = min(100, (int) ($filters['per_page'] ?? 50));

        $base = FeeCollection::with(['student:id,name,student_id', 'batch:id,name'])
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('payment_date', '>=', $v))
            ->when($filters['date_to']   ?? null, fn($q, $v) => $q->whereDate('payment_date', '<=', $v))
            ->when($filters['batch_id']  ?? null, fn($q, $v) => $q->where('batch_id', $v))
            ->when($filters['fee_type']  ?? null, fn($q, $v) => $q->where('fee_type', $v))
            ->orderByDesc('payment_date');

        // Summary over the full matching set (single aggregate query)
        $totals = (clone $base)->selectRaw(
            'SUM(amount_paid) as total_paid,
             GREATEST(0, SUM(amount_due - discount_amount - scholarship_amount - amount_paid)) as total_balance,
             COUNT(*) as total_count'
        )->first();

        $paginated = $base->paginate($perPage);

        return [
            'data'       => collect($paginated->items())->map(fn($f) => [
                'id'                 => $f->id,
                'receipt_no'         => $f->receipt_no,
                'student_name'       => $f->student?->name,
                'student_sid'        => $f->student?->student_id,
                'batch_name'         => $f->batch?->name,
                'fee_type'           => $f->fee_type,
                'month'              => $f->month,
                'amount_due'         => (float) $f->amount_due,
                'discount_amount'    => (float) $f->discount_amount,
                'scholarship_amount' => (float) $f->scholarship_amount,
                'net_amount'         => (float) $f->net_amount,
                'amount_paid'        => (float) $f->amount_paid,
                'balance'            => (float) $f->balance,
                'payment_method'     => $f->payment_method,
                'payment_date'       => $f->payment_date?->toDateString(),
            ]),
            'summary'    => [
                'total_collected'   => round((float) ($totals->total_paid ?? 0), 2),
                'total_outstanding' => round((float) ($totals->total_balance ?? 0), 2),
                'total_count'       => (int) ($totals->total_count ?? 0),
            ],
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ];
    }

    public function duesReport(array $filters): array
    {
        $perPage  = min(100, (int) ($filters['per_page'] ?? 50));
        $month    = $filters['month'] ?? null;
        $batchId  = $filters['batch_id'] ?? null;
        $tenantId = auth()->user()->tenant_id;

        // When month is specified, use LEFT JOIN to include students never billed for that month
        if ($month) {
            $base = DB::table('batch_students')
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
                      ->orWhereRaw(
                          '(fee_collections.amount_due - fee_collections.discount_amount - fee_collections.scholarship_amount - fee_collections.amount_paid) > 0.009'
                      );
                })
                ->when($batchId, fn($q, $v) => $q->where('batches.id', $v));

            $total = (clone $base)->count();

            $totalBalance = (clone $base)->selectRaw('
                SUM(GREATEST(0,
                    COALESCE(fee_collections.amount_due, batches.fee_amount)
                    - COALESCE(fee_collections.discount_amount, 0)
                    - COALESCE(fee_collections.scholarship_amount, 0)
                    - COALESCE(fee_collections.amount_paid, 0)
                )) as total_balance
            ')->value('total_balance');

            $page = max(1, (int) ($filters['page'] ?? 1));
            $rows = (clone $base)->select([
                'students.student_id',
                'students.name as student_name',
                'batches.name as batch_name',
                DB::raw('COALESCE(fee_collections.amount_due, batches.fee_amount) as amount_due'),
                DB::raw('COALESCE(fee_collections.discount_amount, 0) as discount_amount'),
                DB::raw('COALESCE(fee_collections.scholarship_amount, 0) as scholarship_amount'),
                DB::raw('COALESCE(fee_collections.amount_paid, 0) as amount_paid'),
            ])->orderBy('students.name')
              ->offset(($page - 1) * $perPage)
              ->limit($perPage)
              ->get();

            return [
                'data'       => $rows->map(fn($r) => [
                    'student_id'   => $r->student_id,
                    'student_name' => $r->student_name,
                    'batch_name'   => $r->batch_name,
                    'month'        => $month,
                    'amount_due'   => (float) $r->amount_due,
                    'net_amount'   => round(max(0, (float) $r->amount_due - (float) $r->discount_amount - (float) $r->scholarship_amount), 2),
                    'amount_paid'  => (float) $r->amount_paid,
                    'balance'      => round(max(0, (float) $r->amount_due - (float) $r->discount_amount - (float) $r->scholarship_amount - (float) $r->amount_paid), 2),
                ])->values(),
                'summary'    => [
                    'total_outstanding' => round((float) ($totalBalance ?? 0), 2),
                    'total_count'       => $total,
                ],
                'pagination' => [
                    'current_page' => $page,
                    'last_page'    => max(1, (int) ceil($total / $perPage)),
                    'per_page'     => $perPage,
                    'total'        => $total,
                ],
            ];
        }

        // Without month filter: show fee_collection records with outstanding balance
        $base = FeeCollection::with(['student:id,name,student_id', 'batch:id,name'])
            ->whereRaw('(amount_due - discount_amount - scholarship_amount - amount_paid) > 0.009')
            ->when($batchId, fn($q, $v) => $q->where('batch_id', $v))
            ->orderByDesc('created_at');

        $totals   = (clone $base)->selectRaw(
            'SUM(amount_due - discount_amount - scholarship_amount - amount_paid) as total_balance,
             COUNT(*) as total_count'
        )->first();

        $paginated = $base->paginate($perPage);

        return [
            'data'       => collect($paginated->items())->map(fn($f) => [
                'id'           => $f->id,
                'student_name' => $f->student?->name,
                'student_sid'  => $f->student?->student_id,
                'batch_name'   => $f->batch?->name,
                'fee_type'     => $f->fee_type,
                'month'        => $f->month,
                'amount_due'   => (float) $f->amount_due,
                'net_amount'   => (float) $f->net_amount,
                'amount_paid'  => (float) $f->amount_paid,
                'balance'      => (float) $f->balance,
            ]),
            'summary'    => [
                'total_outstanding' => round((float) ($totals->total_balance ?? 0), 2),
                'total_count'       => (int) ($totals->total_count ?? 0),
            ],
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ];
    }

    public function attendanceReport(array $filters): array
    {
        $perPage = min(100, (int) ($filters['per_page'] ?? 50));
        $page    = max(1, (int) ($filters['page'] ?? 1));

        $records = StudentAttendance::with(['student:id,student_id,name'])
            ->when($filters['batch_id']  ?? null, fn($q, $v) => $q->where('batch_id', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($filters['date_to']   ?? null, fn($q, $v) => $q->whereDate('date', '<=', $v))
            ->get();

        $data = $records->groupBy('student_id')
            ->map(function ($rows) {
                $student = $rows->first()->student;
                if (!$student) return null;
                $total   = $rows->count();
                $present = $rows->where('status', 'present')->count();
                $rate    = $total > 0 ? round($present / $total * 100, 1) : 0;

                return [
                    'student_id'      => $student->student_id,
                    'name'            => $student->name,
                    'total'           => $total,
                    'present'         => $present,
                    'absent'          => $rows->where('status', 'absent')->count(),
                    'late'            => $rows->where('status', 'late')->count(),
                    'attendance_rate' => $rate,
                ];
            })
            ->filter()
            ->sortByDesc('attendance_rate')
            ->values();

        $total   = $data->count();
        $slice   = $data->forPage($page, $perPage);

        return [
            'data'       => $slice->values(),
            'summary'    => [
                'total_students'      => $total,
                'avg_attendance_rate' => $data->isNotEmpty() ? round($data->avg('attendance_rate'), 1) : 0,
            ],
            'pagination' => [
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ];
    }

    public function studentListReport(array $filters): array
    {
        $perPage = min(100, (int) ($filters['per_page'] ?? 50));

        $base = Student::with(['batches:id,name,subject'])
            ->when($filters['status']   ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['batch_id'] ?? null, fn($q, $v) =>
                $q->whereHas('batches', fn($bq) => $bq->where('batches.id', $v))
            )
            ->orderBy('name');

        $paginated = $base->paginate($perPage);

        return [
            'data'       => collect($paginated->items())->map(fn($s) => [
                'student_id'     => $s->student_id,
                'name'           => $s->name,
                'phone'          => $s->phone,
                'email'          => $s->email,
                'gender'         => $s->gender,
                'status'         => $s->status,
                'admission_date' => $s->admission_date?->toDateString(),
                'batches'        => $s->batches->map(fn($b) => [
                    'id'      => $b->id,
                    'name'    => $b->name,
                    'subject' => $b->subject,
                ]),
            ]),
            'summary'    => [
                'total_count' => $paginated->total(),
            ],
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ];
    }
}
