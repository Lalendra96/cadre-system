<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKnowledgeSource extends Model
{
    protected $fillable = [
        'title',
        'source_type',
        'language',
        'reference_no',
        'issuing_authority',
        'effective_date',
        'expiry_date',
        'classification',
        'content',
        'source_location',
        'is_verified',
        'is_active',
        'verified_by',
        'verified_at',
        'uploaded_by',
        'supersedes_id',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
