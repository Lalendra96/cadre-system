<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CircularSend extends Model
{
    protected $fillable = [
        'circular_id', 'sent_by', 'position_group_ids',
        'recipient_count', 'sent_count', 'failed_count', 'skipped_no_email_count',
    ];

    protected $casts = [
        'position_group_ids' => 'array',
    ];

    public function circular()
    {
        return $this->belongsTo(Circular::class, 'circular_id');
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
