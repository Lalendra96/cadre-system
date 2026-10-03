<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuplicateResolutionCase extends Model
{
    protected $fillable = [
        'primary_employee_id',
        'duplicate_employee_id',
        'match_key',
        'status',
        'resolution_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];
}
