<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrEscalation extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'rule_code',
        'severity',
        'status',
        'assigned_to',
        'due_at',
        'acknowledged_at',
        'resolved_at',
        'resolution_note',
    ];

    protected $casts = ['due_at' => 'datetime', 'acknowledged_at' => 'datetime', 'resolved_at' => 'datetime'];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
