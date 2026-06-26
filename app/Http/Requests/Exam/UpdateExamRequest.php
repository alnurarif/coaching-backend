<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'subject_id'    => [
                'nullable', 'integer',
                Rule::exists('subjects', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'exam_type_id'  => [
                'nullable', 'integer',
                Rule::exists('exam_types', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'title'         => ['sometimes', 'string', 'max:255'],
            'exam_date'     => ['sometimes', 'date'],
            'total_marks'   => ['sometimes', 'numeric', 'min:1'],
            'passing_marks' => ['sometimes', 'numeric', 'min:0'],
            'status'        => ['sometimes', Rule::in(['draft', 'published', 'completed'])],
            'description'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $exam         = $this->route('exam');
            $totalMarks   = isset($this->total_marks)   ? (float) $this->total_marks   : (float) $exam->total_marks;
            $passingMarks = isset($this->passing_marks) ? (float) $this->passing_marks : (float) $exam->passing_marks;

            if ($passingMarks > $totalMarks) {
                $v->errors()->add('passing_marks', 'Passing marks cannot exceed total marks.');
            }
        });
    }
}
