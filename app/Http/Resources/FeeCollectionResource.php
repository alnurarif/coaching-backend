<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'receipt_no'         => $this->receipt_no,
            'fee_type'           => $this->fee_type,
            'month'              => $this->month,
            'amount_due'         => (float) $this->amount_due,
            'discount_amount'    => (float) $this->discount_amount,
            'scholarship_amount' => (float) $this->scholarship_amount,
            'net_amount'         => $this->net_amount,
            'amount_paid'        => (float) $this->amount_paid,
            'balance'            => $this->balance,
            'payment_date'       => $this->payment_date?->toDateString(),
            'payment_method'     => $this->payment_method,
            'note'               => $this->note,
            'student'            => $this->whenLoaded('student', fn() => [
                'id'         => $this->student->id,
                'student_id' => $this->student->student_id,
                'name'       => $this->student->name,
                'phone'      => $this->student->phone,
            ]),
            'batch'              => $this->whenLoaded('batch', fn() => [
                'id'   => $this->batch->id,
                'name' => $this->batch->name,
            ]),
            'collected_by'       => $this->whenLoaded('collectedBy', fn() => [
                'id'   => $this->collectedBy->id,
                'name' => $this->collectedBy->name,
            ]),
            'created_at'         => $this->created_at?->toDateTimeString(),
        ];
    }
}
