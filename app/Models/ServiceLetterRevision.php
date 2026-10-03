<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceLetterRevision extends Model
{
    protected $fillable = [
        'service_letter_id', 'version_number', 'subject', 'rendered_body', 'reference_no',
        'snapshot_hash', 'change_reason', 'created_by',
    ];

    public function letter()
    {
        return $this->belongsTo(ServiceLetter::class, 'service_letter_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
