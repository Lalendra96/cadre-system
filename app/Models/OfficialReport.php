<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialReport extends Model
{
    protected $fillable = [
        'report_type',
        'period_key',
        'status',
        'payload',
        'prepared_by',
        'checked_by',
        'approved_by',
        'checked_at',
        'approved_at',
        'signature_hash',
    ];

    protected $casts = ['payload' => 'array', 'checked_at' => 'datetime', 'approved_at' => 'datetime'];
}
