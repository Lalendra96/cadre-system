<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterAttachment extends Model
{
    use HasFactory;

    protected $table = 'letter_attachments';

    protected $fillable = ['letter_id', 'file_path', 'original_filename', 'file_size', 'file_removed_at'];

    protected $casts = [
        'file_removed_at' => 'datetime',
        'file_size'       => 'integer',
    ];

    public function letter()
    {
        return $this->belongsTo(Letter::class, 'letter_id');
    }

    public function isAvailable(): bool
    {
        return ! is_null($this->file_path);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
