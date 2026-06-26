<?php

namespace App\Http\Requests\Batch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => [
                'integer',
                Rule::exists('students', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'joined_at'     => ['required', 'date'],
        ];
    }
}
