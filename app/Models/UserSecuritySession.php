<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSecuritySession extends Model
{
    protected $fillable = [
        'user_id', 'session_hash', 'ip_address', 'user_agent', 'device_label',
        'authenticated_at', 'last_activity_at', 'revoked_at', 'revoked_by', 'revoke_reason',
    ];

    protected $casts = [
        'authenticated_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }
}
