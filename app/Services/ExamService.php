<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\GradeScale;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return Exam::with(['batch:id,name', 'subject:id,name', 'examType:id,name'])
            ->withCount('results')
            ->when($filters['batch_id']    ?? null, fn($q, $v) => $q->where('batch_id', $v))
            ->when($filters['subject_id']  ?? null, fn($q, $v) => $q->where('subject_id', $v))
            ->when($filters['exam_type_id'] ?? null, fn($q, $v) => $q->where('exam_type_id', $v))
            ->when($filters['status']      ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['date_from']   ?? null, fn($q, $v) => $q->whereDate('exam_date', '>=', $v))
            ->when($filters['date_to']     ?? null, fn($q, $v) => $q->whereDate('exam_date', '<=', $v))
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->paginate(min(100, (int) ($filters['per_page'] ?? 15)));
    }

    public function create(array $data): Exam
    {
        $exam = Exam::create([
            ...$data,
            'created_by' => auth()->id(),
            'status'     => $data['status'] ?? 'draft',
        ]);

        return $exam->load(['batch:id,name', 'subject:id,name', 'examType:id,name', 'createdBy:id,name']);
    }

    public function update(Exam $exam, array $data): Exam
    {
        $exam->update($data);

        return $exam->fresh(['batch:id,name', 'subject:id,name', 'examType:id,name']);
    }

    public function show(Exam $exam): Exam
    {
        return $exam->load(['batch:id,name', 'subject:id,name', 'examType:id,name', 'createdBy:id,name'])
                    ->loadCount('results');
    }

    public function delete(Exam $exam): void
    {
        $exam->results()->delete();
        $exam->delete();
    }

    /**
     * Returns all students in the batch merged with their existing results for marks entry UI.
     */
    public function getResultsForEntry(Exam $exam): Collection
    {
        $exam->loadMissing('batch.students');

        if ($exam->batch === null) {
            return collect();
        }

        $existing = ExamResult::where('exam_id', $exam->id)
            ->get()
            ->keyBy('student_id');

        return $exam->batch->students->map(fn($student) => [
            'student_id'     => $student->id,
            'student_code'   => $student->student_id,
            'name'           => $student->name,
            'phone'          => $student->phone,
            'marks_obtained' => $existing->get($student->id)?->marks_obtained,
            'is_absent'      => $existing->get($student->id)?->is_absent ?? false,
            'grade'          => $existing->get($student->id)?->grade,
            'position'       => $existing->get($student->id)?->position,
            'remarks'        => $existing->get($student->id)?->remarks,
            'saved'          => $existing->has($student->id),
        ]);
    }

    /**
     * Bulk upsert results, compute grades, recompute positions.
     */
    public function saveResults(Exam $exam, array $records): array
    {
        $tenantId   = auth()->user()->tenant_id;
        $scales     = GradeScale::where('tenant_id', $tenantId)
                                ->orderByDesc('min_percent')
                                ->get();

        DB::transaction(function () use ($exam, $records, $tenantId, $scales) {
            foreach ($records as $record) {
                $isAbsent = (bool) ($record['is_absent'] ?? false);
                $marks    = $isAbsent ? null : (isset($record['marks_obtained']) ? (float) $record['marks_obtained'] : null);
                $grade    = ($marks !== null && !$isAbsent)
                    ? $this->computeGrade($marks, $exam->total_marks, $scales)
                    : null;

                ExamResult::updateOrCreate(
                    [
                        'exam_id'    => $exam->id,
                        'student_id' => $record['student_id'],
                    ],
                    [
                        'tenant_id'      => $tenantId,
                        'marks_obtained' => $marks,
                        'is_absent'      => $isAbsent,
                        'grade'          => $grade,
                        'position'       => null,
                        'remarks'        => $record['remarks'] ?? null,
                    ],
                );
            }

            $this->recomputePositions($exam);
        });

        $total   = count($records);
        $absent  = collect($records)->where('is_absent', true)->count();
        $present = $total - $absent;

        $results = ExamResult::where('exam_id', $exam->id)
            ->whereNotNull('marks_obtained')
            ->where('is_absent', false);

        return [
            'total'       => $total,
            'present'     => $present,
            'absent'      => $absent,
            'avg_marks'   => $results->count() > 0 ? round((float) $results->avg('marks_obtained'), 2) : null,
            'pass_count'  => (clone $results)->where('marks_obtained', '>=', $exam->passing_marks)->count(),
        ];
    }

    /**
     * Result sheet: all students with marks, grade, pass/fail, position.
     */
    public function getResultSheet(Exam $exam): array
    {
        $exam->loadMissing('batch.students');

        if ($exam->batch === null) {
            return [
                'data'    => collect(),
                'summary' => [
                    'total_students' => 0, 'present' => 0, 'absent' => 0,
                    'pass_count' => 0, 'fail_count' => 0,
                    'avg_marks' => null, 'highest_marks' => null, 'lowest_marks' => null,
                ],
            ];
        }

        $results = ExamResult::where('exam_id', $exam->id)
            ->with('student:id,student_id,name,phone')
            ->get()
            ->keyBy('student_id');

        $data = $exam->batch->students->map(fn($student) => [
            'student_id'     => $student->id,
            'student_code'   => $student->student_id,
            'name'           => $student->name,
            'phone'          => $student->phone,
            'marks_obtained' => $results->get($student->id)?->marks_obtained,
            'is_absent'      => $results->get($student->id)?->is_absent ?? false,
            'grade'          => $results->get($student->id)?->grade,
            'position'       => $results->get($student->id)?->position,
            'remarks'        => $results->get($student->id)?->remarks,
            'is_pass'        => $this->isPassing($results->get($student->id), $exam->passing_marks),
            'saved'          => $results->has($student->id),
        ])->values();

        $present = $data->where('is_absent', false)->where('saved', true);

        return [
            'data'    => $data,
            'summary' => [
                'total_students' => $data->count(),
                'present'        => $present->count(),
                'absent'         => $data->where('is_absent', true)->count(),
                'pass_count'     => $present->where('is_pass', true)->count(),
                'fail_count'     => $present->where('is_pass', false)->count(),
                'avg_marks'      => $present->count() > 0
                    ? round((float) $present->avg('marks_obtained'), 2)
                    : null,
                'highest_marks'  => $present->count() > 0
                    ? (float) $present->max('marks_obtained')
                    : null,
                'lowest_marks'   => $present->count() > 0
                    ? (float) $present->min('marks_obtained')
                    : null,
            ],
        ];
    }

    /**
     * Merit list: present students ranked by marks (highest first), ties share rank.
     */
    public function getMeritList(Exam $exam): array
    {
        $results = ExamResult::where('exam_id', $exam->id)
            ->where('is_absent', false)
            ->whereNotNull('marks_obtained')
            ->with(['student:id,student_id,name,phone', 'exam:id,total_marks,passing_marks'])
            ->orderByDesc('marks_obtained')
            ->orderBy('id')
            ->get()
            ->filter(fn($r) => $r->student !== null)
            ->map(function ($r) use ($exam) {
                $r->is_pass = $r->marks_obtained >= $exam->passing_marks;
                return $r;
            })
            ->values();

        return [
            'results' => $results,
            'summary' => [
                'total_appeared' => $results->count(),
                'pass_count'     => $results->where('is_pass', true)->count(),
            ],
        ];
    }

    private function computeGrade(float $marks, float $total, EloquentCollection $scales): ?string
    {
        if ($total <= 0) return null;
        $percent = ($marks / $total) * 100;

        return $scales->first(fn($s) => $percent >= $s->min_percent && $percent <= $s->max_percent)?->label;
    }

    private function recomputePositions(Exam $exam): void
    {
        $results = ExamResult::where('exam_id', $exam->id)
            ->where('is_absent', false)
            ->whereNotNull('marks_obtained')
            ->orderByDesc('marks_obtained')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $position  = 0;
        $rank      = 0;
        $prevMarks = null;
        $groups    = []; // rank => [ids] for bulk update

        foreach ($results as $result) {
            $position++;
            if ($prevMarks === null || abs((float) $result->marks_obtained - (float) $prevMarks) > 0.001) {
                $rank = $position;
            }
            $groups[$rank][] = $result->id;
            $prevMarks = $result->marks_obtained;
        }

        foreach ($groups as $rankValue => $ids) {
            ExamResult::whereIn('id', $ids)->update(['position' => $rankValue]);
        }

        ExamResult::where('exam_id', $exam->id)
            ->where('is_absent', true)
            ->update(['position' => null]);
    }

    private function isPassing(?ExamResult $result, float $passingMarks): ?bool
    {
        if ($result === null) return null;
        if ($result->is_absent) return null;
        if ($result->marks_obtained === null) return null;

        return (float) $result->marks_obtained >= $passingMarks;
    }
}
