<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'email'     => $this->email,
            'phone'     => $this->phone,
            'is_active' => $this->is_active,
            'batches_count' => $this->whenCounted('batches'),
            'profile'   => $this->whenLoaded('teacherProfile', fn() => $this->teacherProfile ? [
                'subject'       => $this->teacherProfile->subject,
                'qualification' => $this->teacherProfile->qualification,
                'address'       => $this->teacherProfile->address,
                'join_date'     => $this->teacherProfile->join_date?->toDateString(),
                'base_salary'   => (float) $this->teacherProfile->base_salary,
            ] : null),
            'batches'   => $this->whenLoaded('batches', fn() =>
                $this->batches->map(fn($b) => [
                    'id'       => $b->id,
                    'name'     => $b->name,
                    'subject'  => $b->subject,
                    'status'   => $b->status,
                    'schedule' => $b->schedule ?? [],
                ])
            ),
            'branch'    => $this->whenLoaded('branch', fn() => $this->branch ? [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ] : null),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
