<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterComment extends Model
{
    protected $fillable = ['letter_id', 'user_id', 'body', 'comment_type', 'quoted_text', 'is_resolved', 'resolved_by', 'resolved_at'];

    protected $casts = ['is_resolved' => 'boolean', 'resolved_at' => 'datetime'];

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
