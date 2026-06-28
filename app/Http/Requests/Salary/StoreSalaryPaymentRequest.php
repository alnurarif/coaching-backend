<?php

namespace App\Http\Requests\Salary;

use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\SalaryService;
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
            'amount_paid'    => ['required', 'numeric', 'min:0.01'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'bkash', 'nagad', 'rocket', 'bank_transfer'])],
            'note'           => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $user = User::with('teacherProfile')->find($this->user_id);

            if (! $user) {
                return;
            }

            if (! $user->hasAnyRole(SalaryService::PAYROLL_ROLES)) {
                $v->errors()->add('user_id', 'The selected user is not eligible for payroll.');
                return;
            }

            // Reject if the submitted base_salary doesn't match the employee's actual salary.
            // This prevents UI bypass attacks where someone inflates the base_salary via a raw API call.
            $actualBaseSalary = $user->baseSalary();
            if (abs((float) $this->base_salary - $actualBaseSalary) > 0.01) {
                $v->errors()->add('base_salary', 'Base salary does not match the employee\'s current salary on record.');
                return;
            }

            $net  = max(0, $actualBaseSalary + (float) ($this->bonus ?? 0) - (float) ($this->deduction ?? 0));
            $paid = (float) $this->amount_paid;

            if ($this->user_id && $this->month) {
                $alreadyPaidTotal = (float) SalaryPayment::where('user_id', $this->user_id)
                    ->where('month', $this->month)
                    ->sum('amount_paid');

                if (($alreadyPaidTotal + $paid) > $net) {
                    $remaining = max(0, $net - $alreadyPaidTotal);
                    $v->errors()->add('amount_paid', "Amount paid exceeds remaining salary. Remaining: ৳{$remaining}");
                }
            } elseif ($paid > $net) {
                $v->errors()->add('amount_paid', 'Amount paid cannot exceed the net salary.');
            }
        });
    }
}
