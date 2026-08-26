<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Circular extends Model
{
    use HasDisableWorkflow;

    public const CATEGORY_LABELS = [
        'memo'               => 'Memo',
        'circular'           => 'Circular',
        'internal_circular'  => 'Internal Circular',
    ];

    protected $fillable = [
        'title', 'description', 'category',
        'file_path', 'original_filename', 'mime_type', 'file_size',
        'share_token', 'view_count', 'total_sends', 'uploaded_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'file_size'   => 'integer',
        'view_count'  => 'integer',
        'total_sends' => 'integer',
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Circular $circular) {
            if (empty($circular->share_token)) {
                // 40 chars from Str::random() (CSPRNG-backed) — far beyond
                // brute-force feasibility as a URL segment. Retried on the
                // vanishingly unlikely chance of a collision.
                do {
                    $token = Str::random(40);
                } while (static::where('share_token', $token)->exists());

                $circular->share_token = $token;
            }
        });
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sends()
    {
        return $this->hasMany(CircularSend::class, 'circular_id');
    }

    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }

    public function getFileSizeForHumansAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
