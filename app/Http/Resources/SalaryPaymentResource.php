<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'receipt_no'     => $this->receipt_no,
            'month'          => $this->month,
            'base_salary'    => (float) $this->base_salary,
            'bonus'          => (float) $this->bonus,
            'deduction'      => (float) $this->deduction,
            'net_salary'     => $this->net_salary,
            'amount_paid'    => (float) $this->amount_paid,
            'payment_date'   => $this->payment_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'note'           => $this->note,
            'teacher'        => $this->whenLoaded('teacher', fn() => [
                'id'       => $this->teacher->id,
                'name'     => $this->teacher->name,
                'position' => ucfirst($this->teacher->roles->first()?->name ?? 'staff'),
            ]),
            'paid_by_user'   => $this->whenLoaded('paidBy', fn() => [
                'id'   => $this->paidBy->id,
                'name' => $this->paidBy->name,
            ]),
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
