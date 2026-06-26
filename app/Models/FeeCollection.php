<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeCollection extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'batch_id',
        'collected_by',
        'fee_type',
        'month',
        'amount_due',
        'discount_amount',
        'scholarship_amount',
        'amount_paid',
        'payment_date',
        'payment_method',
        'receipt_no',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount_due'         => 'decimal:2',
            'discount_amount'    => 'decimal:2',
            'scholarship_amount' => 'decimal:2',
            'amount_paid'        => 'decimal:2',
            'payment_date'       => 'date',
        ];
    }

    public function getNetAmountAttribute(): float
    {
        return max(0, (float) $this->amount_due - (float) $this->discount_amount - (float) $this->scholarship_amount);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, $this->net_amount - (float) $this->amount_paid);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
