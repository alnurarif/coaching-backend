<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'tenant_id', 'is_system'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            $tenantId = auth()->check() ? auth()->user()->tenant_id : null;

            if ($tenantId === null) {
                return;
            }

            $query->where(function (Builder $q) use ($tenantId) {
                $q->whereNull('tenant_id')
                  ->orWhere('tenant_id', $tenantId);
            });
        });

        // Auto-assign tenant_id when creating a non-system custom role
        static::creating(function (self $role) {
            if (! $role->is_system && $role->tenant_id === null && auth()->check()) {
                $role->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
