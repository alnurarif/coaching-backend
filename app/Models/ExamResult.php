<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'exam_id',
        'student_id',
        'marks_obtained',
        'is_absent',
        'grade',
        'position',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'marks_obtained' => 'float',
            'is_absent'      => 'boolean',
            'position'       => 'integer',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
