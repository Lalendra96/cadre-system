<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarPassTemplate extends Model
{
    protected $fillable = [
        'name',
        'code',
        'image_path',
        'original_filename',
        'mime_type',
        'file_size',
        'allowed_position_ids',
        'default_validity_months',
        'version',
        'is_active',
        'uploaded_by',
        'disabled_by',
        'disabled_at',
        'disable_reason',
    ];

    protected $casts = [
        'allowed_position_ids' => 'array',
        'default_validity_months' => 'integer',
        'version' => 'integer',
        'is_active' => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function supportsPosition(?int $positionId): bool
    {
        return $positionId !== null
            && in_array($positionId, array_map('intval', $this->allowed_position_ids ?? []), true);
    }
}
