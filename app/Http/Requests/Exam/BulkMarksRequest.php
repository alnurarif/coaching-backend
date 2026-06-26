<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkMarksRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'records'                   => ['required', 'array', 'min:1'],
            'records.*.student_id'      => [
                'required', 'integer',
                Rule::exists('students', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'records.*.marks_obtained'  => ['nullable', 'numeric', 'min:0'],
            'records.*.is_absent'       => ['required', 'boolean'],
            'records.*.remarks'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $exam = $this->route('exam');

            $exam->loadMissing('batch.students');
            $batchStudentIds = $exam->batch?->students?->pluck('id')->toArray() ?? [];

            foreach ($this->input('records', []) as $i => $record) {
                $studentId = (int) ($record['student_id'] ?? 0);
                $isAbsent  = (bool) ($record['is_absent'] ?? false);

                // Batch membership check
                if ($studentId && $batchStudentIds && !in_array($studentId, $batchStudentIds, true)) {
                    $v->errors()->add(
                        "records.{$i}.student_id",
                        "Student does not belong to this exam's batch."
                    );
                }

                // Marks required when present
                if (!$isAbsent && !isset($record['marks_obtained'])) {
                    $v->errors()->add(
                        "records.{$i}.marks_obtained",
                        'Marks are required for students who are not absent.'
                    );
                }

                // Marks cannot exceed total
                if (!$isAbsent && isset($record['marks_obtained'])) {
                    if ((float) $record['marks_obtained'] > $exam->total_marks) {
                        $v->errors()->add(
                            "records.{$i}.marks_obtained",
                            "Marks cannot exceed total marks ({$exam->total_marks})."
                        );
                    }
                }
            }
        });
    }
}
