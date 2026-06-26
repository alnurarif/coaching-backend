<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkStudentAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_id'            => [
                'required',
                Rule::exists('batches', 'id')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->where('status', 'active'),
            ],
            'date'                => ['required', 'date'],
            'records'             => ['required', 'array', 'min:1'],
            'records.*.student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->where('status', 'active'),
                Rule::exists('batch_students', 'student_id')->where('batch_id', $this->input('batch_id')),
            ],
            'records.*.status'    => ['required', Rule::in(['present', 'absent', 'late'])],
            'records.*.note'      => ['nullable', 'string', 'max:255'],
        ];
    }
}
