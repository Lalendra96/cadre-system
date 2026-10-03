<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationIssue extends Model
{
    protected $fillable = [
        'position_id',
        'year',
        'month',
        'severity',
        'status',
        'evidence',
        'explanation',
        'resolution_note',
        'assigned_to',
        'verified_by',
        'resolved_at',
        'verified_at',
    ];

    protected $casts = ['evidence' => 'array', 'resolved_at' => 'datetime', 'verified_at' => 'datetime'];
}
