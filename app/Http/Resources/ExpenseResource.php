<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'amount'         => $this->amount,
            'expense_date'   => $this->expense_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'reference_no'   => $this->reference_no,
            'notes'          => $this->notes,
            'category'       => $this->whenLoaded('category', fn() => [
                'id'    => $this->category->id,
                'name'  => $this->category->name,
                'color' => $this->category->color,
            ]),
            'branch'       => $this->whenLoaded('branch', fn() => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'recorded_by'  => $this->whenLoaded('recordedBy', fn() => [
                'id'   => $this->recordedBy->id,
                'name' => $this->recordedBy->name,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
