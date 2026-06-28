<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'branch_id'   => $this->branch_id,
            'is_active'   => $this->is_active,
            'base_salary' => (float) ($this->base_salary ?? 0),
            'role'        => $this->roles->first()?->name,
            'created_at'  => $this->created_at?->toDateString(),
        ];
    }
}
