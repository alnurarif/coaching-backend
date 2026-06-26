<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where(
                    $builder->getModel()->getTable() . '.tenant_id',
                    auth()->user()->tenant_id
                );
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && ! $model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
