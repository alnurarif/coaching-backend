<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['sometimes', 'string', 'max:255'],
            'branch_id'           => ['sometimes', 'nullable', 'integer', Rule::exists('branches', 'id')->where('tenant_id', $this->user()->tenant_id)->where('is_active', true)],
            'date_of_birth'       => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender'              => ['sometimes', 'nullable', Rule::in(['male', 'female', 'other'])],
            'phone'               => ['sometimes', 'nullable', 'string', 'max:20'],
            'email'               => ['sometimes', 'nullable', 'email', 'max:255'],
            'address'             => ['sometimes', 'nullable', 'string', 'max:500'],
            'admission_date'      => ['sometimes', 'date', 'before_or_equal:today'],
            'status'              => ['sometimes', Rule::in(['active', 'inactive', 'withdrawn'])],
            'photo'               => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'guardian'            => ['sometimes', 'nullable', 'array'],
            'guardian.name'       => ['required_with:guardian', 'string', 'max:255'],
            'guardian.relation'   => ['required_with:guardian', Rule::in(['father', 'mother', 'guardian'])],
            'guardian.phone'      => ['required_with:guardian', 'string', 'max:20'],
            'guardian.email'      => ['sometimes', 'nullable', 'email', 'max:255'],
            'guardian.occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
