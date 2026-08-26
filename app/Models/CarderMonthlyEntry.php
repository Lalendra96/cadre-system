<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarderMonthlyEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'carder_monthly_entries';

    public const STATUS_SUBMITTED           = 'submitted';
    public const STATUS_VERIFIED            = 'verified';
    public const STATUS_AMENDMENT_REQUESTED = 'amendment_requested';
    public const STATUS_CANCELLED           = 'cancelled';

    protected $fillable = [
        // Core data
        'subject_code_id',
        'position_id',
        'year',
        'month',
        'males',
        'females',
        'in_position',
        'transferred_in',
        'transferred_out',
        'no_pay_leave',
        'approved_amount',
        'remarks',

        // Workflow state
        'status',
        'is_locked',

        // Submission
        'submitted_by',
        'submitted_at',

        // Verification
        'verified_by',
        'verified_at',

        // Amendment
        'amendment_reason',
        'amendment_requested_by',
        'amendment_requested_at',

        // Cancellation
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'year'                    => 'integer',
        'month'                   => 'integer',
        'males'                   => 'integer',
        'females'                 => 'integer',
        'in_position'             => 'integer',
        'transferred_in'          => 'integer',
        'transferred_out'         => 'integer',
        'no_pay_leave'            => 'integer',
        'approved_amount'         => 'integer',
        'is_locked'               => 'boolean',
        'submitted_at'            => 'datetime',
        'verified_at'             => 'datetime',
        'amendment_requested_at'  => 'datetime',
        'cancelled_at'            => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function subjectCode()
    {
        return $this->belongsTo(SubjectCode::class, 'subject_code_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function amendmentRequestedBy()
    {
        return $this->belongsTo(User::class, 'amendment_requested_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function transferRecords()
    {
        return $this->hasMany(TransferRecord::class, 'carder_entry_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopePendingVerification($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED)
            ->whereNotNull('submitted_at');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    public function scopeAmendmentRequested($query)
    {
        return $query->where('status', self::STATUS_AMENDMENT_REQUESTED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    // ── Actions ────────────────────────────────────────────────────────────

    /**
     * Cancel this entry. Sets status to 'cancelled' and locks it.
     * Uses direct update() so all fields go through the model's
     * mass-assignment guard (all cancel fields are in $fillable).
     */
    public function cancel(int $userId, string $reason): bool
    {
        return $this->update([
            'status'        => self::STATUS_CANCELLED,
            'is_locked'     => true,
            'cancelled_by'  => $userId,
            'cancelled_at'  => now(),
            'cancel_reason' => $reason,
        ]);
    }

    // ── Static helpers ─────────────────────────────────────────────────────

    /**
     * Returns a Collection of CarderMonthlyEntry models for the given period,
     * applying carry-forward for any subject-code/position pair that has no
     * entry in the exact (year, month) but has a prior entry.
     *
     * CARRY-FORWARD RULE
     * ──────────────────
     * Exact match found  →  returned with  is_carried_forward = false
     * No exact match     →  most recent prior entry used,
     *                       is_carried_forward = true
     *
     * This ensures reports always show a real figure (last known) rather
     * than zero/null for periods where an officer hasn't submitted yet.
     *
     * EFFICIENCY
     * ──────────
     * Two DB round-trips regardless of how many subject-code/position pairs
     * exist:
     *   1. One Eloquent query  — exact period entries (with full casting)
     *   2. One raw PostgreSQL  — most-recent prior entry per pair via
     *      DISTINCT ON (far more efficient than N subquery loops)
     *
     * Results are hydrated back to model instances so casts, accessors,
     * and lazy-loaded relations all work normally on the returned collection.
     *
     * @return \Illuminate\Support\Collection<static>
     */
    public static function forPeriodWithCarryForward(int $year, int $month): \Illuminate\Support\Collection
    {
        // ── 1. Exact entries for this period ──────────────────────────────

        $exact = static::where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->get();

        // Tag every exact entry so callers can detect it
        $exact->each(fn ($e) => $e->setAttribute('is_carried_forward', false));

        // Index covered pairs: "subjectCodeId_positionId" => true
        $covered = $exact->mapWithKeys(
            fn ($e) => ["{$e->subject_code_id}_{$e->position_id}" => true]
        );

        // ── 2. Most-recent prior entry per pair (PostgreSQL DISTINCT ON) ──
        //    DISTINCT ON guarantees exactly one row per (subject_code_id, position_id),
        //    choosing the row with the highest year then highest month.

        $table = (new static())->getTable();

        $rawRows = \Illuminate\Support\Facades\DB::select(
            "SELECT DISTINCT ON (subject_code_id, position_id) *
             FROM {$table}
             WHERE deleted_at  IS NULL
               AND status      != :cancelled
               AND (
                     year  < :year
                  OR (year = :year2 AND month < :month)
               )
             ORDER BY subject_code_id, position_id, year DESC, month DESC",
            [
                'cancelled' => self::STATUS_CANCELLED,
                'year'      => $year,
                'year2'     => $year,
                'month'     => $month,
            ]
        );

        // Hydrate raw stdClass rows back into proper Eloquent model instances.
        // hydrate() restores $casts, accessors, and dirty tracking — relations
        // can still be lazy-loaded or eager-loaded afterwards.
        $carried = static::hydrate(
            array_map(fn (\stdClass $row) => (array) $row, $rawRows)
        )
        ->reject(fn ($e) => $covered->has("{$e->subject_code_id}_{$e->position_id}"))
        ->each(fn ($e) => $e->setAttribute('is_carried_forward', true));

        return $exact->concat($carried);
    }

    public static function isDeadlinePassed(int $year, int $month): bool
    {
        if (! SystemSetting::getBool('deadline_enforcement_enabled', true)) {
            return false;
        }

        $deadlineDay  = (int) SystemSetting::get('deadline_day', 15);
        $graceDays    = (int) SystemSetting::get('deadline_grace_days', 0);
        $deadline     = now()->setYear($year)->setMonth($month + 1)->setDay($deadlineDay)->startOfDay();
        $deadline->addDays($graceDays);

        return now()->isAfter($deadline);
    }

    public static function deadlineFor(int $year, int $month): \Carbon\Carbon
    {
        $deadlineDay = (int) SystemSetting::get('deadline_day', 15);
        $graceDays   = (int) SystemSetting::get('deadline_grace_days', 0);

        return now()->setYear($year)->setMonth($month + 1)->setDay($deadlineDay)
            ->startOfDay()
            ->addDays($graceDays);
    }
}
