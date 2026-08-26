<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

/**
 * A Super-Admin-granted, time-boxed authorization for a user to act as
 * the Subject Officer for a subject code they don't permanently hold.
 * See User::effectiveSubjectCodeIds() for how this merges with permanent
 * assignments at authorization-check time.
 */
class ActingSubjectOfficer extends Model
{
    use HasDisableWorkflow;

    protected $table = 'acting_subject_officers';

    protected $fillable = [
        'user_id', 'subject_code_id', 'start_date', 'end_date', 'appointed_by', 'reason',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subjectCode()
    {
        return $this->belongsTo(SubjectCode::class, 'subject_code_id');
    }

    public function appointedBy()
    {
        return $this->belongsTo(User::class, 'appointed_by');
    }

    /** Assignments that are active AND within their date window right now. */
    public function scopeCurrentlyEffective($query)
    {
        return $query->active()
            ->where('start_date', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()));
    }
}
