<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrHandover extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'effective_on' => 'date',
        'accepted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function responsibility(): BelongsTo
    {
        return $this->belongsTo(HrResponsibility::class, 'hr_responsibility_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
