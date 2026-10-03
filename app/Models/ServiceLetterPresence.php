<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceLetterPresence extends Model
{
    protected $table = 'service_letter_presence';

    protected $fillable = ['service_letter_id', 'user_id', 'last_seen_at'];

    protected $casts = ['last_seen_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
