<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'subject'        => $this->subject,
            'capacity'       => $this->capacity,
            'fee_amount'     => (float) $this->fee_amount,
            'start_date'     => $this->start_date?->toDateString(),
            'status'         => $this->status,
            'schedule'       => $this->schedule ?? [],
            'student_count'  => $this->whenCounted('students'),
            'branch_id'      => $this->branch_id,
            'teacher_id'     => $this->teacher_id,
            'branch'         => $this->whenLoaded('branch', fn() => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'teacher'        => $this->whenLoaded('teacher', fn() => $this->teacher ? [
                'id'   => $this->teacher->id,
                'name' => $this->teacher->name,
            ] : null),
            'students'       => $this->whenLoaded('students', fn() =>
                $this->students->map(fn($s) => [
                    'id'         => $s->id,
                    'student_id' => $s->student_id,
                    'name'       => $s->name,
                    'phone'      => $s->phone,
                    'status'     => $s->status,
                    'joined_at'  => $s->pivot->joined_at,
                ])
            ),
        ];
    }
}
