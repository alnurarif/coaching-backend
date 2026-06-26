<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'is_active'  => $this->is_active,
            'role'       => $this->roles->first()?->name,
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
