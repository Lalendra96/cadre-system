<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

/**
 * Flexible grading-ladder configuration for a Position.
 * `criteria` is intentionally untyped JSON — see migration docblock.
 *
 * ONE RECOGNIZED KEY: `min_years_in_grade` (integer, years). Set on the
 * TARGET grade — e.g. on "Grade II" to mean "an employee needs this many
 * years in Grade III before becoming eligible for Grade II." When
 * present, App\Console\Commands\NotifyGradePromotionEligibility checks it
 * automatically against every employee's time in their current grade and
 * notifies both Super Admin and the employee's Subject Officer once the
 * threshold is met. All other criteria keys remain free-form/descriptive
 * only — this is the one key the system actively acts on.
 */
class PositionGrade extends Model
{
    use HasDisableWorkflow;

    protected $fillable = [
        'position_id', 'name', 'sort_order', 'criteria', 'description',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'criteria'    => 'array',
        'sort_order'  => 'integer',
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function gradeRecords()
    {
        return $this->hasMany(EmployeeGradeRecord::class, 'position_grade_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Human-readable summary of this grade's flexible criteria, e.g.
     * "Min. 5 years service · Diploma required · Exam required".
     * Used in views so the UI doesn't need to know the criteria schema.
     */
    public function criteriaSummary(): string
    {
        if (empty($this->criteria)) {
            return 'No specific criteria configured';
        }

        $parts = [];
        foreach ($this->criteria as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            if (is_bool($value)) {
                if ($value) $parts[] = $label;
            } else {
                $parts[] = "{$label}: {$value}";
            }
        }

        return $parts ? implode(' · ', $parts) : 'No specific criteria configured';
    }

    /**
     * The recognized `min_years_in_grade` criteria value, if configured
     * and numeric — null otherwise (not configured, or the Super Admin
     * entered a non-numeric value for it, which is treated as "not set"
     * rather than guessed at).
     */
    public function minYearsInGrade(): ?float
    {
        $value = $this->criteria['min_years_in_grade'] ?? null;
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * The next higher grade in this position's ladder — the grade with
     * the smallest sort_order that is still greater than this grade's
     * own sort_order, for the same position. Null if this is already the
     * most senior configured grade.
     */
    public function nextGrade(): ?self
    {
        return static::active()
            ->where('position_id', $this->position_id)
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order')
            ->first();
    }
}
