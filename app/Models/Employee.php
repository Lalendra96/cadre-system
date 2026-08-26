<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasDisableWorkflow, HasFactory, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        // Identity
        'salutation',
        'name',
        'pay_no',
        'nic_number',
        'wop_number',
        'gender',

        // Contact
        'email',
        'whatsapp_mobile',

        // Organisational links
        'subject_code_id',
        'position_id',
        'unit_id',
        'salary_scale_id',

        // Service details
        'date_of_birth',
        'date_of_appointment',
        'date_reported_for_duty',
        'retirement_age',
        'is_confirmed',
        'date_confirmed',
        'confirmation_reference_no',
        'notes',

        // Status
        'is_active',

        // Audit
        'created_by',
        'updated_by',

        // Disable workflow (from HasDisableWorkflow)
        'disabled_by',
        'disabled_at',
        'disable_reason',
    ];

    protected $casts = [
        'date_of_birth'          => 'date',
        'date_of_appointment'    => 'date',
        'date_reported_for_duty' => 'date',
        'retirement_age'         => 'integer',
        'is_confirmed'           => 'boolean',
        'date_confirmed'         => 'date',
        'is_active'              => 'boolean',
        'disabled_at'            => 'datetime',
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

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function salaryScale()
    {
        return $this->belongsTo(SalaryScale::class, 'salary_scale_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function actingAppointments()
    {
        return $this->hasMany(ActingAppointment::class, 'employee_id');
    }

    public function transferRecords()
    {
        return $this->hasMany(TransferRecord::class, 'employee_id');
    }

    public function incrementRecords()
    {
        return $this->hasMany(EmployeeIncrement::class, 'employee_id');
    }

    public function gradeRecords()
    {
        return $this->hasMany(EmployeeGradeRecord::class, 'employee_id');
    }

    public function qualifications()
    {
        return $this->hasMany(EmployeeQualification::class, 'employee_id');
    }

    public function examRecords()
    {
        return $this->hasMany(EmployeeExamRecord::class, 'employee_id');
    }

    public function serviceLetters()
    {
        return $this->hasMany(ServiceLetter::class, 'employee_id');
    }

    public function interdictions()
    {
        return $this->hasMany(EmployeeInterdiction::class, 'employee_id');
    }

    public function leaveRecords()
    {
        return $this->hasMany(EmployeeLeaveRecord::class, 'employee_id');
    }

    // ── Computed ───────────────────────────────────────────────────────────

    public function getRetireDateAttribute(): ?\Carbon\Carbon
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->addYears($this->retirement_age ?? 60);
    }

    public function getMonthsToRetirementAttribute(): ?int
    {
        if (! $this->retire_date) {
            return null;
        }

        return (int) now()->diffInMonths($this->retire_date, false);
    }

    public function getServiceYearsAttribute(): ?int
    {
        if (! $this->date_of_appointment) {
            return null;
        }

        return (int) $this->date_of_appointment->diffInYears(now());
    }

    public function getRetirementStatusAttribute(): string
    {
        $months = $this->months_to_retirement;

        if ($months === null) return 'unknown';
        if ($months < 0)   return 'overdue';
        if ($months <= 6)  return 'critical';
        if ($months <= 12) return 'soon';
        if ($months <= 24) return 'upcoming';

        return 'normal';
    }

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->salutation ? $this->salutation . ' ' : '') . $this->name);
    }

    /**
     * NIC with all but the last 4 characters masked, e.g. "*****4567V".
     * DATA SECURITY: use this in every list/table/report view. The full
     * nic_number should only ever be rendered on the single-employee edit
     * form, where the officer already has legitimate access to that one
     * record and needs to verify/correct the full value.
     */
    public function getMaskedNicAttribute(): ?string
    {
        if (! $this->nic_number) {
            return null;
        }

        $visible = substr($this->nic_number, -4);
        $masked  = str_repeat('*', max(strlen($this->nic_number) - 4, 0));

        return $masked . $visible;
    }

    /**
     * The employee's currently-held grade record (no end_date, or an
     * end_date that hasn't passed yet), if any has been recorded.
     */
    public function getCurrentGradeAttribute(): ?EmployeeGradeRecord
    {
        return $this->gradeRecords()
            ->current()
            ->with('positionGrade')
            ->orderByDesc('effective_date')
            ->first();
    }

    /**
     * The next upcoming increment that hasn't been notified yet, or the
     * most recent past increment if nothing is upcoming.
     */
    public function getNextIncrementAttribute(): ?EmployeeIncrement
    {
        return $this->incrementRecords()
            ->active()
            ->whereDate('increment_date', '>=', now()->toDateString())
            ->orderBy('increment_date')
            ->first()
            ?? $this->incrementRecords()->mostRecent()->first();
    }

    /**
     * True if this employee has an interdiction record that is still
     * 'ongoing' — they substantively hold their post but are not
     * actively performing duty. Callers computing "actual filled
     * headcount" for operational purposes may want to exclude these;
     * this system does not automatically subtract them from any existing
     * report (that would silently change historical figures other
     * features already depend on) — it exposes the fact so a report can
     * choose to account for it explicitly.
     */
    public function isCurrentlyInterdicted(): bool
    {
        return $this->interdictions()->active()->where('inquiry_status', 'ongoing')->exists();
    }

    /**
     * True if this employee has a leave record whose expected or actual
     * return date has not yet passed (or has no return date recorded at
     * all, treated conservatively as "still away").
     */
    public function isCurrentlyOnLeave(): bool
    {
        return $this->leaveRecords()
            ->active()
            ->whereNull('actual_return_date')
            ->where('start_date', '<=', now()->toDateString())
            ->exists();
    }
}
