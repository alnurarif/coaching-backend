<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'title'               => ['required', 'string', 'max:200'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'expense_date'        => ['required', 'date', 'before_or_equal:today'],
            'expense_category_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('tenant_id', $tenantId),
            ],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('tenant_id', $tenantId),
            ],
            'payment_method' => ['sometimes', 'in:cash,bkash,nagad,rocket,bank_transfer'],
            'reference_no'   => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }
}
