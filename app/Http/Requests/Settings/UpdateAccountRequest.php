<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['sometimes', 'string', 'max:255'],
            'email'            => ['sometimes', 'email', Rule::unique('users', 'email')->ignore(auth()->id())],
            'phone'            => ['sometimes', 'nullable', 'string', 'max:20'],
            'current_password' => ['required_with:new_password', 'string'],
            'new_password'     => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->filled('new_password') && !Hash::check($this->current_password ?? '', auth()->user()->password)) {
                $v->errors()->add('current_password', 'Current password is incorrect.');
            }
        });
    }
}
