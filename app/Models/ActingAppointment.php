<?php

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActingAppointment extends Model
{
    use HasDisableWorkflow, HasFactory;

    protected $table = 'acting_appointments';

    protected $fillable = [
        'employee_id', 'acting_position_id', 'substantive_position_id',
        'start_date', 'end_date', 'appointment_order_no', 'remarks',
        'is_active', 'created_by',
        'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function actingPosition()
    {
        return $this->belongsTo(Position::class, 'acting_position_id');
    }

    public function substantivePosition()
    {
        return $this->belongsTo(Position::class, 'substantive_position_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
            ->where(fn ($s) => $s->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }

    // ── Computed ───────────────────────────────────────────────────────────

    public function getDurationAttribute(): string
    {
        $start = $this->start_date;
        $end   = $this->end_date ?? now()->toDate();
        $days  = $start->diffInDays($end);
        return $days < 30 ? "{$days}d" : round($days / 30.44, 1) . 'm';
    }
}
