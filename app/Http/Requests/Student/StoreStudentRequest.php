<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'branch_id'           => ['nullable', 'integer', Rule::exists('branches', 'id')->where('tenant_id', $this->user()->tenant_id)->where('is_active', true)],
            'date_of_birth'       => ['nullable', 'date', 'before:today'],
            'gender'              => ['nullable', Rule::in(['male', 'female', 'other'])],
            'phone'               => ['nullable', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:255'],
            'address'             => ['nullable', 'string', 'max:500'],
            'admission_date'      => ['required', 'date', 'before_or_equal:today'],
            'status'              => ['sometimes', Rule::in(['active', 'inactive'])],
            'photo'               => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'guardian'            => ['nullable', 'array'],
            'guardian.name'       => ['required_with:guardian', 'string', 'max:255'],
            'guardian.relation'   => ['required_with:guardian', Rule::in(['father', 'mother', 'guardian'])],
            'guardian.phone'      => ['required_with:guardian', 'string', 'max:20'],
            'guardian.email'      => ['nullable', 'email', 'max:255'],
            'guardian.occupation' => ['nullable', 'string', 'max:255'],
        ];
    }
}
