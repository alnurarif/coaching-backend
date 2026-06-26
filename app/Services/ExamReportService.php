<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;

class ExamReportService
{
    public function studentProgress(array $filters): array
    {
        $tenantId = auth()->user()->tenant_id;

        $student = Student::where('tenant_id', $tenantId)
                          ->findOrFail($filters['student_id']);

        $results = ExamResult::with([
            'exam' => fn($q) => $q->with(['subject:id,name', 'examType:id,name', 'batch:id,name']),
        ])
            ->where('student_id', $student->id)
            ->whereHas('exam', function ($q) use ($filters, $tenantId) {
                $q->where('tenant_id', $tenantId);
                if ($filters['batch_id']   ?? null) $q->where('batch_id',   $filters['batch_id']);
                if ($filters['subject_id'] ?? null) $q->where('subject_id', $filters['subject_id']);
                if ($filters['date_from']  ?? null) $q->whereDate('exam_date', '>=', $filters['date_from']);
                if ($filters['date_to']    ?? null) $q->whereDate('exam_date', '<=', $filters['date_to']);
            })
            ->get()
            ->sortBy('exam.exam_date')
            ->values();

        $timeline = $results->map(function ($r) {
            $exam     = $r->exam;
            $total    = (float) $exam->total_marks;
            $obtained = $r->is_absent ? null : (float) $r->marks_obtained;
            $percent  = ($obtained !== null && $total > 0)
                ? round($obtained / $total * 100, 2)
                : null;

            return [
                'exam_id'        => $r->exam_id,
                'title'          => $exam->title,
                'exam_date'      => $exam->exam_date?->toDateString(),
                'subject_name'   => $exam->subject?->name ?? 'General',
                'exam_type'      => $exam->examType?->name,
                'batch_name'     => $exam->batch?->name,
                'total_marks'    => $total,
                'marks_obtained' => $obtained,
                'percent'        => $percent,
                'grade'          => $r->grade,
                'position'       => $r->position,
                'is_absent'      => $r->is_absent,
                'is_pass'        => ($obtained !== null)
                    ? $obtained >= (float) $exam->passing_marks
                    : null,
            ];
        });

        $appeared = $timeline->filter(fn($r) => !$r['is_absent'] && $r['percent'] !== null);

        $bySubject = $appeared
            ->groupBy('subject_name')
            ->map(fn($rows, $subject) => [
                'subject_name'    => $subject,
                'exams_count'     => $rows->count(),
                'avg_percent'     => round((float) $rows->avg('percent'), 2),
                'highest_percent' => (float) $rows->max('percent'),
                'lowest_percent'  => (float) $rows->min('percent'),
            ])
            ->values();

        return [
            'student'    => [
                'id'         => $student->id,
                'student_id' => $student->student_id,
                'name'       => $student->name,
                'phone'      => $student->phone,
            ],
            'timeline'   => $timeline->values(),
            'by_subject' => $bySubject,
            'summary'    => [
                'total_exams'     => $timeline->count(),
                'appeared'        => $appeared->count(),
                'absent_count'    => $timeline->where('is_absent', true)->count(),
                'avg_percent'     => $appeared->count() > 0 ? round((float) $appeared->avg('percent'), 2) : null,
                'highest_percent' => $appeared->count() > 0 ? (float) $appeared->max('percent') : null,
                'pass_count'      => $timeline->where('is_pass', true)->count(),
                'fail_count'      => $timeline->where('is_pass', false)->count(),
            ],
        ];
    }

    public function batchAnalytics(array $filters): array
    {
        $tenantId = auth()->user()->tenant_id;

        $batch = Batch::where('tenant_id', $tenantId)
                      ->with('students:id,student_id,name')
                      ->findOrFail($filters['batch_id']);

        $exams = Exam::with(['subject:id,name', 'examType:id,name'])
            ->where('batch_id', $batch->id)
            ->where('tenant_id', $tenantId)
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('exam_date', '>=', $v))
            ->when($filters['date_to']   ?? null, fn($q, $v) => $q->whereDate('exam_date', '<=', $v))
            ->orderBy('exam_date')
            ->get();

        $totalStudents = $batch->students->count();

        if ($exams->isEmpty()) {
            return [
                'batch'       => ['id' => $batch->id, 'name' => $batch->name],
                'per_exam'    => [],
                'per_student' => [],
                'summary'     => ['total_exams' => 0, 'total_students' => $totalStudents, 'avg_pass_rate' => null],
            ];
        }

        $examIds    = $exams->pluck('id');
        $studentIds = $batch->students->pluck('id');

        // Single query for all results
        $allResults = ExamResult::whereIn('exam_id', $examIds)
            ->get()
            ->groupBy('exam_id');

        $perExam = $exams->map(function ($exam) use ($allResults, $totalStudents) {
            $results  = $allResults->get($exam->id, collect());
            $appeared = $results->where('is_absent', false)->whereNotNull('marks_obtained');
            $passCount = $appeared->filter(
                fn($r) => (float) $r->marks_obtained >= (float) $exam->passing_marks
            )->count();

            $avgMarks = $appeared->count() > 0 ? round((float) $appeared->avg('marks_obtained'), 2) : null;
            $avgPct   = ($avgMarks !== null && (float) $exam->total_marks > 0)
                ? round($avgMarks / (float) $exam->total_marks * 100, 2)
                : null;

            return [
                'exam_id'        => $exam->id,
                'title'          => $exam->title,
                'exam_date'      => $exam->exam_date?->toDateString(),
                'subject_name'   => $exam->subject?->name,
                'exam_type'      => $exam->examType?->name,
                'total_marks'    => (float) $exam->total_marks,
                'total_students' => $totalStudents,
                'appeared'       => $appeared->count(),
                'absent'         => $results->where('is_absent', true)->count(),
                'avg_marks'      => $avgMarks,
                'avg_percent'    => $avgPct,
                'pass_count'     => $passCount,
                'pass_rate'      => $appeared->count() > 0
                    ? round($passCount / $appeared->count() * 100, 2)
                    : null,
                'highest'        => $appeared->count() > 0 ? (float) $appeared->max('marks_obtained') : null,
                'lowest'         => $appeared->count() > 0 ? (float) $appeared->min('marks_obtained') : null,
            ];
        });

        // Per-student stats — load results scoped to batch students
        $studentResults = ExamResult::whereIn('exam_id', $examIds)
            ->whereIn('student_id', $studentIds)
            ->with('student:id,student_id,name')
            ->get()
            ->groupBy('student_id');

        $examsById = $exams->keyBy('id');

        $perStudent = $studentResults->map(function ($rows) use ($examsById) {
            $student = $rows->first()->student;
            if (!$student) return null;

            $appeared = $rows->where('is_absent', false)->whereNotNull('marks_obtained');

            $percents = $appeared->map(function ($r) use ($examsById) {
                $exam = $examsById->get($r->exam_id);
                if (!$exam || (float) $exam->total_marks <= 0) return null;
                return (float) $r->marks_obtained / (float) $exam->total_marks * 100;
            })->filter()->values();

            $passCount = $appeared->filter(function ($r) use ($examsById) {
                $exam = $examsById->get($r->exam_id);
                return $exam && (float) $r->marks_obtained >= (float) $exam->passing_marks;
            })->count();

            return [
                'student_id'      => $student->student_id,
                'name'            => $student->name,
                'exams_appeared'  => $appeared->count(),
                'avg_percent'     => $percents->count() > 0 ? round((float) $percents->avg(), 2) : null,
                'highest_percent' => $percents->count() > 0 ? round((float) $percents->max(), 2) : null,
                'pass_count'      => $passCount,
                'fail_count'      => max(0, $appeared->count() - $passCount),
            ];
        })->filter()->sortByDesc('avg_percent')->values();

        $examPassRates = $perExam->whereNotNull('pass_rate');

        return [
            'batch'       => ['id' => $batch->id, 'name' => $batch->name],
            'per_exam'    => $perExam->values(),
            'per_student' => $perStudent,
            'summary'     => [
                'total_exams'    => $exams->count(),
                'total_students' => $totalStudents,
                'avg_pass_rate'  => $examPassRates->count() > 0
                    ? round((float) $examPassRates->avg('pass_rate'), 2)
                    : null,
            ],
        ];
    }
}
