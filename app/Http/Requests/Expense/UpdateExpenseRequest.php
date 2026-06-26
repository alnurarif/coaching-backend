<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'title'               => ['sometimes', 'string', 'max:200'],
            'amount'              => ['sometimes', 'numeric', 'min:0.01'],
            'expense_date'        => ['sometimes', 'date', 'before_or_equal:today'],
            'expense_category_id' => [
                'sometimes', 'nullable',
                Rule::exists('expense_categories', 'id')->where('tenant_id', $tenantId),
            ],
            'branch_id' => [
                'sometimes', 'nullable',
                Rule::exists('branches', 'id')->where('tenant_id', $tenantId),
            ],
            'payment_method' => ['sometimes', 'in:cash,bkash,nagad,rocket,bank_transfer'],
            'reference_no'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'notes'          => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
