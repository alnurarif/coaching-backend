<?php

namespace App\Http\Requests\Fee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreFeeCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id'         => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'batch_id'           => [
                'required',
                'integer',
                Rule::exists('batches', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'fee_type'           => ['required', Rule::in(['admission', 'monthly', 'exam', 'other'])],
            'month'              => ['required_if:fee_type,monthly', 'nullable', 'date_format:Y-m'],
            'amount_due'         => ['required', 'numeric', 'min:0'],
            'discount_amount'    => ['nullable', 'numeric', 'min:0'],
            'scholarship_amount' => ['nullable', 'numeric', 'min:0'],
            'amount_paid'        => ['required', 'numeric', 'min:0'],
            'payment_date'       => ['required', 'date'],
            'payment_method'     => ['required', Rule::in(['cash', 'bkash', 'nagad', 'rocket', 'bank_transfer'])],
            'note'               => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $due         = (float) $this->amount_due;
            $discount    = (float) ($this->discount_amount ?? 0);
            $scholarship = (float) ($this->scholarship_amount ?? 0);
            $paid        = (float) $this->amount_paid;
            $net         = max(0, $due - $discount - $scholarship);

            if (($discount + $scholarship) > $due) {
                $validator->errors()->add('discount_amount', 'Total deductions cannot exceed the amount due.');
            }

            if ($paid > $net) {
                $validator->errors()->add('amount_paid', 'Amount paid cannot exceed the net payable amount.');
            }

            $studentId = (int) $this->student_id;
            $batchId   = (int) $this->batch_id;
            if ($studentId && $batchId) {
                $enrolled = DB::table('batch_students')
                    ->join('batches', 'batches.id', '=', 'batch_students.batch_id')
                    ->where('batch_students.student_id', $studentId)
                    ->where('batch_students.batch_id', $batchId)
                    ->where('batches.tenant_id', $this->user()->tenant_id)
                    ->exists();

                if (!$enrolled) {
                    $validator->errors()->add('student_id', 'The student is not enrolled in the selected batch.');
                }
            }

            // Prevent duplicate monthly fee for the same student+batch+month
            if ($this->fee_type === 'monthly' && $this->month && $studentId && $batchId) {
                $duplicate = DB::table('fee_collections')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->where('student_id', $studentId)
                    ->where('batch_id', $batchId)
                    ->where('fee_type', 'monthly')
                    ->where('month', $this->month)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add('month', 'A monthly fee record already exists for this student and batch for ' . $this->month . '.');
                }
            }
        });
    }
}
