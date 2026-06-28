<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'students_limit',
        'branches_limit',
        'staff_limit',
        'reports_level',
        'can_export',
        'support_level',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price'          => 'decimal:2',
            'students_limit' => 'integer',
            'branches_limit' => 'integer',
            'staff_limit'    => 'integer',
            'can_export'     => 'boolean',
            'is_active'      => 'boolean',
            'sort_order'     => 'integer',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function isUnlimited(string $resource): bool
    {
        return is_null($this->{$resource . '_limit'});
    }
}
