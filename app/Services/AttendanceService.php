<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function getStudentAttendance(int $batchId, string $date): Collection
    {
        $batch = Batch::with(['students' => fn($q) => $q->where('status', 'active')])->findOrFail($batchId);

        $existing = StudentAttendance::where('batch_id', $batchId)
            ->where('date', $date)
            ->get()
            ->keyBy('student_id');

        return $batch->students->map(fn($student) => [
            'student_id' => $student->id,
            'student_code' => $student->student_id,
            'name'       => $student->name,
            'phone'      => $student->phone,
            'status'     => $existing->get($student->id)?->status ?? null,
            'note'       => $existing->get($student->id)?->note ?? null,
            'marked'     => $existing->has($student->id),
        ]);
    }

    public function markStudentAttendance(array $data): array
    {
        $tenantId = auth()->user()->tenant_id;
        $date     = $data['date'];
        $batchId  = $data['batch_id'];

        DB::transaction(function () use ($data, $tenantId, $date, $batchId) {
            foreach ($data['records'] as $record) {
                StudentAttendance::updateOrCreate(
                    [
                        'batch_id'   => $batchId,
                        'student_id' => $record['student_id'],
                        'date'       => $date,
                    ],
                    [
                        'tenant_id' => $tenantId,
                        'status'    => $record['status'],
                        'note'      => $record['note'] ?? null,
                    ],
                );
            }
        });

        $summary = collect($data['records']);

        return [
            'date'     => $date,
            'total'    => $summary->count(),
            'present'  => $summary->where('status', 'present')->count(),
            'absent'   => $summary->where('status', 'absent')->count(),
            'late'     => $summary->where('status', 'late')->count(),
        ];
    }

    public function getTeacherAttendance(string $date, int $tenantId): Collection
    {
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $existing = TeacherAttendance::where('tenant_id', $tenantId)
            ->where('date', $date)
            ->get()
            ->keyBy('user_id');

        return $teachers->map(fn($teacher) => [
            'user_id' => $teacher->id,
            'name'    => $teacher->name,
            'phone'   => $teacher->phone,
            'status'  => $existing->get($teacher->id)?->status ?? null,
            'note'    => $existing->get($teacher->id)?->note ?? null,
            'marked'  => $existing->has($teacher->id),
        ]);
    }

    public function markTeacherAttendance(array $data): array
    {
        $user     = auth()->user();
        $date     = $data['date'];

        DB::transaction(function () use ($data, $user, $date) {
            foreach ($data['records'] as $record) {
                TeacherAttendance::updateOrCreate(
                    [
                        'user_id' => $record['user_id'],
                        'date'    => $date,
                    ],
                    [
                        'tenant_id' => $user->tenant_id,
                        'branch_id' => $user->branch_id,
                        'status'    => $record['status'],
                        'note'      => $record['note'] ?? null,
                    ],
                );
            }
        });

        $summary = collect($data['records']);

        return [
            'date'    => $date,
            'total'   => $summary->count(),
            'present' => $summary->where('status', 'present')->count(),
            'absent'  => $summary->where('status', 'absent')->count(),
            'late'    => $summary->where('status', 'late')->count(),
        ];
    }

    public function getAbsentList(string $date, int $tenantId): Collection
    {
        return StudentAttendance::with(['student', 'batch'])
            ->where('tenant_id', $tenantId)
            ->where('date', $date)
            ->where('status', 'absent')
            ->get()
            ->filter(fn($a) => $a->student !== null && $a->batch !== null)
            ->map(fn($a) => [
                'student_id'   => $a->student->student_id,
                'name'         => $a->student->name,
                'phone'        => $a->student->phone,
                'batch'        => $a->batch->name,
                'note'         => $a->note,
            ])->values();
    }

    public function getStudentReport(array $filters): array
    {
        $perPage = min(100, (int) ($filters['per_page'] ?? 50));
        $page    = max(1, (int) ($filters['page'] ?? 1));

        $paginator = StudentAttendance::with(['student:id,student_id,name'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereHas('student')
            ->when($filters['batch_id']   ?? null, fn($q, $v) => $q->where('batch_id', $v))
            ->when($filters['student_id'] ?? null, fn($q, $v) => $q->where('student_id', $v))
            ->when($filters['date_from']  ?? null, fn($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($filters['date_to']    ?? null, fn($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($filters['status']     ?? null, fn($q, $v) => $q->where('status', $v))
            ->orderBy('date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn($a) => [
                'date'       => $a->date->toDateString(),
                'student_id' => $a->student->student_id,
                'name'       => $a->student->name,
                'status'     => $a->status,
                'note'       => $a->note,
            ])->values(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ];
    }
}
