<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'is_active'   => $this->is_active,
            'tenant_id'   => $this->tenant_id,
            'branch_id'   => $this->branch_id,
            'roles'       => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'tenant'      => $this->whenLoaded('tenant', fn() => [
                'id'      => $this->tenant->id,
                'name'    => $this->tenant->name,
                'slug'    => $this->tenant->slug,
                'phone'   => $this->tenant->phone,
                'email'   => $this->tenant->email,
                'address' => $this->tenant->address,
                'logo'    => $this->tenant->logo,
            ]),
            'branch'      => $this->whenLoaded('branch', fn() => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
        ];
    }
}
