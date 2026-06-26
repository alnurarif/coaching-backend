<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeScale extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'label',
        'min_percent',
        'max_percent',
        'gpa',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_percent' => 'float',
            'max_percent' => 'float',
            'gpa'         => 'float',
            'sort_order'  => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
