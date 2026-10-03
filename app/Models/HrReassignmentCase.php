<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrReassignmentCase extends Model
{
    public const OPEN_STATUSES = ['unassigned', 'needs_review', 'awaiting_acceptance'];

    protected $guarded = ['id'];

    protected $casts = ['accepted_at' => 'datetime', 'work_transfer' => 'array'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function proposedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_user_id');
    }

    public function acceptedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }
}
