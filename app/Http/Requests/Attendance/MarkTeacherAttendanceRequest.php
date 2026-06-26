<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkTeacherAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'               => ['required', 'date'],
            'records'            => ['required', 'array', 'min:1'],
            'records.*.user_id'  => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->whereNull('deleted_at'),
            ],
            'records.*.status'   => ['required', Rule::in(['present', 'absent', 'late'])],
            'records.*.note'     => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            foreach ($this->records ?? [] as $i => $record) {
                $userId = $record['user_id'] ?? null;
                if (!$userId) continue;
                $user = \App\Models\User::find($userId);
                if ($user && !$user->hasRole('teacher')) {
                    $v->errors()->add("records.{$i}.user_id", 'The selected user is not a teacher.');
                }
            }
        });
    }
}
