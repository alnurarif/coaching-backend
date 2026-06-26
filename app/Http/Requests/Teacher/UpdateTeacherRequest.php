<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teacher = $this->route('teacher');

        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'email'         => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher)],
            'phone'         => ['sometimes', 'nullable', 'string', 'max:20'],
            'password'      => ['sometimes', 'nullable', 'string', 'min:8'],
            'is_active'     => ['sometimes', 'boolean'],
            'subject'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'qualification' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address'       => ['sometimes', 'nullable', 'string', 'max:500'],
            'join_date'     => ['sometimes', 'nullable', 'date'],
            'base_salary'   => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
