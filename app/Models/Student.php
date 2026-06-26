<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'student_id',
        'name',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'address',
        'photo',
        'admission_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'  => 'date',
            'admission_date' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'batch_students')
            ->withPivot('joined_at')
            ->withTimestamps();
    }
}
