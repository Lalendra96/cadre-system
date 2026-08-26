<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

/**
 * A formal, e-signed declaration that a position under a subject code has
 * an available vacancy. See migration docblock for the 90-day cooldown
 * business rule enforced in VacancyAvailabilityLetterController::store().
 */
class VacancyAvailabilityLetter extends Model
{
    use HasDisableWorkflow;

    protected $fillable = [
        'position_id', 'subject_code_id', 'vacancy_count', 'reference_no', 'remarks',
        'issued_by', 'issued_at', 'e_signature_id', 'cooldown_until',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'vacancy_count'  => 'integer',
        'issued_at'      => 'datetime',
        'cooldown_until' => 'date',
        'is_active'      => 'boolean',
        'disabled_at'    => 'datetime',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function subjectCode()
    {
        return $this->belongsTo(SubjectCode::class, 'subject_code_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function eSignature()
    {
        return $this->belongsTo(ESignature::class, 'e_signature_id');
    }

    /**
     * True while this letter's 90-day window has not yet elapsed.
     *
     * This single condition serves TWO purposes by design:
     *   1. It IS the "currently available vacancy" a Subject Officer sees
     *      on their dashboard for this position/subject code.
     *   2. It is exactly what blocks a NEW letter being issued for the
     *      same position+subject_code pair (see
     *      VacancyAvailabilityLetterController::store()).
     * Once cooldown_until passes, the declaration is considered stale and
     * a new one may be issued.
     */
    public function isInCooldown(): bool
    {
        return $this->is_active && $this->cooldown_until->isFuture();
    }

    public function daysRemainingInCooldown(): int
    {
        return $this->isInCooldown() ? (int) now()->diffInDays($this->cooldown_until, false) : 0;
    }

    public function scopeForPosition($query, int $positionId, int $subjectCodeId)
    {
        return $query->where('position_id', $positionId)->where('subject_code_id', $subjectCodeId);
    }

    /**
     * Currently-valid vacancy declarations — i.e. still within their 90-day
     * window. This is what Subject Officers see as "available vacancies"
     * for their subject codes, and what the store() guard checks against
     * before allowing a new letter for the same position+subject_code.
     */
    public function scopeCurrentlyAvailable($query)
    {
        return $query->active()->whereDate('cooldown_until', '>=', now()->toDateString());
    }
}
