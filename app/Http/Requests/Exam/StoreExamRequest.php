<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'batch_id'      => [
                'required', 'integer',
                Rule::exists('batches', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'subject_id'    => [
                'nullable', 'integer',
                Rule::exists('subjects', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'exam_type_id'  => [
                'nullable', 'integer',
                Rule::exists('exam_types', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'title'         => ['required', 'string', 'max:255'],
            'exam_date'     => ['required', 'date'],
            'total_marks'   => ['required', 'numeric', 'min:1'],
            'passing_marks' => ['required', 'numeric', 'min:0'],
            'status'        => ['sometimes', Rule::in(['draft', 'published', 'completed'])],
            'description'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ((float) $this->passing_marks > (float) $this->total_marks) {
                $v->errors()->add('passing_marks', 'Passing marks cannot exceed total marks.');
            }
        });
    }
}
