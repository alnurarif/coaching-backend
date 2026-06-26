<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'month',
        'base_salary',
        'bonus',
        'deduction',
        'amount_paid',
        'payment_date',
        'payment_method',
        'receipt_no',
        'paid_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'base_salary'  => 'decimal:2',
            'bonus'        => 'decimal:2',
            'deduction'    => 'decimal:2',
            'amount_paid'  => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function getNetSalaryAttribute(): float
    {
        return max(0, (float) $this->base_salary + (float) $this->bonus - (float) $this->deduction);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
