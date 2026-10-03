<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterPresence extends Model
{
    protected $table = 'letter_presence';

    protected $fillable = ['letter_id', 'user_id', 'last_seen_at'];

    protected $casts = ['last_seen_at' => 'datetime'];

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
