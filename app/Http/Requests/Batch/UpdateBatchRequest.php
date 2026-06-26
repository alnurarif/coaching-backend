<?php

namespace App\Http\Requests\Batch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                    => ['sometimes', 'string', 'max:255'],
            'branch_id'               => ['sometimes', 'nullable', 'integer', Rule::exists('branches', 'id')->where('tenant_id', $this->user()->tenant_id)->where('is_active', true)],
            'subject_id'              => ['sometimes', 'nullable', 'integer', Rule::exists('subjects', 'id')->where('tenant_id', $this->user()->tenant_id)],
            'subject'                 => ['sometimes', 'nullable', 'string', 'max:255'],
            'teacher_id'              => [
                'sometimes',
                'nullable',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->whereNull('deleted_at'),
            ],
            'capacity'                => ['sometimes', 'integer', 'min:1', 'max:500'],
            'fee_amount'              => ['sometimes', 'numeric', 'min:0'],
            'start_date'              => ['sometimes', 'date'],
            'status'                  => ['sometimes', Rule::in(['active', 'inactive', 'completed'])],
            'schedule'                => ['sometimes', 'nullable', 'array'],
            'schedule.*.day'          => ['required_with:schedule', 'string', 'max:20'],
            'schedule.*.start_time'   => ['required_with:schedule', 'date_format:H:i'],
            'schedule.*.end_time'     => ['required_with:schedule', 'date_format:H:i', 'after:schedule.*.start_time'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $teacherId = (int) $this->teacher_id;
            if ($teacherId) {
                $teacher = \App\Models\User::find($teacherId);
                if ($teacher && ! $teacher->hasRole('teacher')) {
                    $v->errors()->add('teacher_id', 'The selected user is not a teacher.');
                }
            }
        });
    }
}
