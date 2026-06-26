<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'student_id'     => $this->student_id,
            'name'           => $this->name,
            'date_of_birth'  => $this->date_of_birth?->toDateString(),
            'gender'         => $this->gender,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'address'        => $this->address,
            'photo'          => $this->photo ? Storage::disk('public')->url($this->photo) : null,
            'admission_date' => $this->admission_date?->toDateString(),
            'status'         => $this->status,
            'branch_id'      => $this->branch_id,
            'branch'         => $this->whenLoaded('branch', fn() => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'guardian'       => $this->whenLoaded('guardian', fn() => $this->guardian ? [
                'id'         => $this->guardian->id,
                'name'       => $this->guardian->name,
                'relation'   => $this->guardian->relation,
                'phone'      => $this->guardian->phone,
                'email'      => $this->guardian->email,
                'occupation' => $this->guardian->occupation,
            ] : null),
            'created_at'     => $this->created_at?->toDateString(),
        ];
    }
}
