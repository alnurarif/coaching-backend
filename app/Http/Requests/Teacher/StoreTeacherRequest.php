<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone'         => ['nullable', 'string', 'max:20'],
            'password'      => ['required', 'string', 'min:8'],
            'subject'       => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'address'       => ['nullable', 'string', 'max:500'],
            'join_date'     => ['nullable', 'date'],
            'base_salary'   => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
