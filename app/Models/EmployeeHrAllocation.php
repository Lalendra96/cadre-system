<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeHrAllocation extends Model
{
    protected $fillable = [
        'employee_id',
        'user_id',
        'position_id',
        'starts_on',
        'ends_on',
        'assigned_by',
        'reason',
        'ended_at',
        'ended_by',
        'end_reason',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'ended_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeEffective($query)
    {
        return $query
            ->whereNull('ended_at')
            ->whereDate('starts_on', '<=', today())
            ->where(
                fn ($q) => $q
                    ->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', today())
            );
    }
}
