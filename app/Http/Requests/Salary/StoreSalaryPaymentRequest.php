<?php

namespace App\Http\Requests\Salary;

use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'        => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->whereNull('deleted_at'),
            ],
            'month'          => ['required', 'date_format:Y-m'],
            'base_salary'    => ['required', 'numeric', 'min:0'],
            'bonus'          => ['nullable', 'numeric', 'min:0'],
            'deduction'      => ['nullable', 'numeric', 'min:0'],
            'amount_paid'    => ['required', 'numeric', 'min:0'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'bkash', 'nagad', 'rocket', 'bank_transfer'])],
            'note'           => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $user = User::find($this->user_id);
            if ($user && ! $user->hasRole('teacher')) {
                $v->errors()->add('user_id', 'The selected user is not a teacher.');
            }

            $net  = max(0, (float) $this->base_salary + (float) ($this->bonus ?? 0) - (float) ($this->deduction ?? 0));
            $paid = (float) $this->amount_paid;

            if ($paid > $net) {
                $v->errors()->add('amount_paid', 'Amount paid cannot exceed the net salary.');
            }

            if ($this->user_id && $this->month) {
                $alreadyPaid = SalaryPayment::where('user_id', $this->user_id)
                    ->where('month', $this->month)
                    ->exists();

                if ($alreadyPaid) {
                    $v->errors()->add('month', 'Salary for this month has already been paid for this teacher.');
                }
            }
        });
    }
}
