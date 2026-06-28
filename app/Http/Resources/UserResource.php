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
                'plan'    => $this->tenant->relationLoaded('plan') && $this->tenant->plan ? [
                    'id'              => $this->tenant->plan->id,
                    'name'            => $this->tenant->plan->name,
                    'slug'            => $this->tenant->plan->slug,
                    'students_limit'  => $this->tenant->plan->students_limit,
                    'branches_limit'  => $this->tenant->plan->branches_limit,
                    'staff_limit'     => $this->tenant->plan->staff_limit,
                    'reports_level'   => $this->tenant->plan->reports_level,
                    'can_export'      => $this->tenant->plan->can_export,
                    'support_level'   => $this->tenant->plan->support_level,
                ] : null,
            ]),
            'branch'      => $this->whenLoaded('branch', fn() => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
        ];
    }
}
