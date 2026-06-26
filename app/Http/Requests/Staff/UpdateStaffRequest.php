<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'string', 'max:255'],
            'email'     => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($this->route('staff'))],
            'phone'     => ['sometimes', 'nullable', 'string', 'max:20'],
            'password'  => ['sometimes', 'nullable', 'string', 'min:8'],
            'role'      => ['sometimes', 'string', function ($attribute, $value, $fail) {
                if (in_array($value, ['owner', 'teacher'])) {
                    return $fail('The selected role cannot be assigned to staff.');
                }
                $exists = \App\Models\Role::withoutGlobalScopes()
                    ->where('name', $value)
                    ->where(function ($q) { $q->whereNull('tenant_id')->orWhere('tenant_id', $this->user()->tenant_id); })
                    ->exists();
                if (! $exists) {
                    $fail('The selected role does not exist.');
                }
            }],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
