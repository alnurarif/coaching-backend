<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'teacher_id',
        'subject_id',
        'name',
        'subject',
        'capacity',
        'schedule',
        'fee_amount',
        'start_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'schedule'   => 'array',
            'fee_amount' => 'decimal:2',
            'start_date' => 'date',
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

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'batch_students')
            ->withPivot('joined_at')
            ->withTimestamps();
    }
}
