<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherProfile extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'qualification',
        'address',
        'join_date',
        'base_salary',
    ];

    protected function casts(): array
    {
        return [
            'join_date'   => 'date',
            'base_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
