<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letter extends Model
{
    use \App\Traits\HasDisableWorkflow;
    use HasFactory, SoftDeletes;

    protected $table = 'letters';

    protected $fillable = [
        'created_by', 'subject_code_id', 'title', 'description',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subjectCode()
    {
        return $this->belongsTo(SubjectCode::class, 'subject_code_id');
    }

    public function recipients()
    {
        return $this->hasMany(LetterRecipient::class, 'letter_id');
    }

    /** The "stack" of files shared with this letter. */
    public function attachments()
    {
        return $this->hasMany(LetterAttachment::class, 'letter_id');
    }

    public function hasAvailableAttachments(): bool
    {
        return $this->attachments->contains(fn ($a) => $a->isAvailable());
    }

    /** True once every recipient has at least opened the letter. */
    public function getIsReadByAllAttribute(): bool
    {
        return $this->recipients->isNotEmpty() && $this->recipients->every(fn ($r) => $r->is_read);
    }
}
