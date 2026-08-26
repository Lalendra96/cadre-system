<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class PositionActingAllowanceRule extends Model
{
    use HasDisableWorkflow;

    public const RULE_TYPE_PCT_DIFFERENCE = 'percentage_of_salary_difference';
    public const RULE_TYPE_PCT_BASE       = 'percentage_of_base';
    public const RULE_TYPE_FLAT           = 'flat_amount';

    public const RULE_TYPE_LABELS = [
        self::RULE_TYPE_PCT_DIFFERENCE => '% of salary difference (acting scale − substantive scale)',
        self::RULE_TYPE_PCT_BASE       => '% of the acting position\'s own salary scale',
        self::RULE_TYPE_FLAT           => 'Flat Rs. amount',
    ];

    protected $fillable = [
        'position_id', 'rule_type', 'percentage', 'flat_amount', 'notes', 'created_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'percentage'  => 'decimal:2',
        'flat_amount' => 'decimal:2',
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    /** The position this rule applies to when acted IN (acting_appointments.acting_position_id). */
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
