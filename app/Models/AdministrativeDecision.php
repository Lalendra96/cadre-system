<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdministrativeDecision extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPES = [
        'grade_change' => 'Grade Change / Promotion Record',
        'increment_record' => 'Increment Record',
        'transfer_record' => 'Transfer Record',
        'acting_appointment' => 'Acting Appointment',
        'interdiction_record' => 'Interdiction / Disciplinary Record',
        'confirmation_status' => 'Confirmation-in-Service Status',
        'leave_record' => 'Leave / No-Pay Leave Record',
    ];

    protected $fillable = [
        'decision_type',
        'employee_id',
        'summary',
        'payload',
        'source_reference',
        'business_rule_id',
        'status',
        'requested_by',
        'requested_at',
        'decided_by',
        'decided_at',
        'decision_reason',
        'applied_model_type',
        'applied_model_id',
    ];

    protected $casts = [
        'payload' => 'encrypted:array',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function requester()
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }

    public function decider()
    {
        return $this->belongsTo(
            User::class,
            'decided_by'
        );
    }

    public function businessRule()
    {
        return $this->belongsTo(BusinessRule::class);
    }

    public function events()
    {
        return $this->hasMany(
            AdministrativeDecisionEvent::class
        )->orderBy('id');
    }

    public function scopePending($query)
    {
        return $query->where(
            'status',
            self::STATUS_PENDING
        );
    }

    public function canBeDecidedBy(User $user): bool
    {
        return $user->is_active
            && (
                $user->isSuperAdmin()
                || $user->isAdministrativeOfficer()
            )
            && $user->id !== $this->requested_by;
    }
}
