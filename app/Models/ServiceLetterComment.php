<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceLetterComment extends Model
{
    protected $fillable = ['service_letter_id', 'user_id', 'body', 'is_resolved', 'resolved_by', 'resolved_at'];

    protected $casts = ['is_resolved' => 'boolean', 'resolved_at' => 'datetime'];

    public function letter()
    {
        return $this->belongsTo(ServiceLetter::class, 'service_letter_id');
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
