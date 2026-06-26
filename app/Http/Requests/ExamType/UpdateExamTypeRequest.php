<?php

namespace App\Http\Requests\ExamType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['sometimes', 'boolean'],
        ];
    }
}
