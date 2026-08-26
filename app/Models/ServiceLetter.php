<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class ServiceLetter extends Model
{
    use HasDisableWorkflow;

    public const STATUS_DRAFT             = 'draft';
    public const STATUS_PENDING_APPROVAL  = 'pending_approval';
    public const STATUS_APPROVED          = 'approved';
    public const STATUS_REJECTED          = 'rejected';

    protected $fillable = [
        'employee_id', 'template_id', 'subject', 'rendered_body', 'status',
        'drafted_by', 'approved_by', 'approved_at', 'e_signature_id', 'rejection_reason',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function template()
    {
        return $this->belongsTo(ServiceLetterTemplate::class, 'template_id');
    }

    public function draftedBy()
    {
        return $this->belongsTo(User::class, 'drafted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function eSignature()
    {
        return $this->belongsTo(ESignature::class, 'e_signature_id');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }
}
