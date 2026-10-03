<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtilityBill extends Model
{
    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partially_paid';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'utility_account_id',
        'bill_reference',
        'billing_period_start',
        'billing_period_end',
        'bill_date',
        'due_date',
        'previous_balance',
        'current_charges',
        'adjustments',
        'bill_amount',
        'amount_paid',
        'status',
        'consumption_value',
        'consumption_unit',
        'remarks',
        'recorded_by',
        'updated_by',
    ];

    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'bill_date' => 'date',
        'due_date' => 'date',
        'previous_balance' => 'decimal:2',
        'current_charges' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'bill_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(UtilityAccount::class, 'utility_account_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(UtilityBillPayment::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getOutstandingAttribute(): float
    {
        return max(0, (float) $this->bill_amount - (float) $this->amount_paid);
    }
}
