<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\EncryptsPersonnelData;
use Illuminate\Database\Eloquent\Model;

class EmployeeChangeRequest extends Model
{
    use EncryptsPersonnelData;

    protected array $encryptedPii = [
        'old_value' => 'generic',
        'requested_value' => 'generic',
    ];
    protected array $searchablePii = [];

    protected $fillable = [
        'employee_id',
        'field_name',
        'old_value',
        'requested_value',
        'status',
        'reason',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'administrative_decision_id',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
