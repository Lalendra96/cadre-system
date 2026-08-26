<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A single in-app notification for one user.
 *
 * Always query through scopeForUser() — never fetch by raw ID alone from
 * a controller, or one user could mark/read another user's notification
 * (IDOR). See NotificationController for the pattern this enforces.
 */
class Notification extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'link_url', 'data', 'is_read', 'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    /**
     * Mandatory ownership scope. Every query in NotificationController
     * chains this first — it is the single point that prevents a user
     * from ever touching another user's notification row.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    // ── Actions ────────────────────────────────────────────────────────────

    public function markRead(): bool
    {
        if ($this->is_read) {
            return true; // already read — idempotent no-op
        }

        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
