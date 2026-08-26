<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class EmployeeExamRecord extends Model
{
    use HasDisableWorkflow;

    public const TYPE_COMPETITIVE     = 'competitive';
    public const TYPE_EFFICIENCY_BAR  = 'efficiency_bar';

    public const TYPE_LABELS = [
        self::TYPE_COMPETITIVE    => 'Competitive Examination',
        self::TYPE_EFFICIENCY_BAR => 'Efficiency Bar (E-Bar)',
    ];

    public const RESULT_LABELS = [
        'pass'    => 'Pass',
        'fail'    => 'Fail',
        'pending' => 'Pending',
    ];

    protected $fillable = [
        'employee_id', 'exam_type', 'exam_name', 'exam_date', 'result',
        'reference_no', 'notes', 'recorded_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'exam_date'   => 'date',
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeEfficiencyBar($query)
    {
        return $query->where('exam_type', self::TYPE_EFFICIENCY_BAR);
    }

    public function scopePending($query)
    {
        return $query->where('result', 'pending');
    }
}
