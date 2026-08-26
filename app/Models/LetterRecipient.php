<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterRecipient extends Model
{
    use HasFactory;

    protected $table = 'letter_recipients';

    public const STATUSES = ['pending', 'reviewed', 'approved', 'rejected'];

    protected $fillable = ['letter_id', 'user_id', 'status', 'remarks', 'is_read', 'read_at'];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function letter()
    {
        return $this->belongsTo(Letter::class, 'letter_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Marks this recipient's copy as read, if not already. Idempotent. */
    public function markRead(): void
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }
}
