<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterRevision extends Model
{
    protected $fillable = [
        'letter_id', 'version_number', 'title', 'live_content', 'reference_no', 'letterhead_id', 'document_classification',
        'contains_personal_data', 'retention_category', 'access_note', 'review_due_date', 'snapshot_hash', 'change_reason', 'created_by',
    ];

    protected $casts = ['contains_personal_data' => 'boolean', 'review_due_date' => 'date'];

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
