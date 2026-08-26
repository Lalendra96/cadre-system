<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeImportBatch extends Model
{
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_MAPPED   = 'mapped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED   = 'failed';

    protected $fillable = [
        'subject_code_id', 'default_position_id', 'uploaded_by', 'original_filename', 'stored_path', 'file_type',
        'column_headers', 'column_mapping', 'status',
        'total_rows', 'imported_count', 'error_count', 'errors', 'completed_at',
    ];

    protected $casts = [
        'column_headers' => 'array',
        'column_mapping' => 'array',
        'errors'         => 'array',
        'completed_at'   => 'datetime',
    ];

    public function subjectCode()
    {
        return $this->belongsTo(SubjectCode::class, 'subject_code_id');
    }

    public function defaultPosition()
    {
        return $this->belongsTo(\App\Models\Position::class, 'default_position_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getSkippedCountAttribute(): int
    {
        return max($this->total_rows - $this->imported_count, 0);
    }

    public function getSuccessRateAttribute(): int
    {
        return $this->total_rows > 0 ? (int) round($this->imported_count / $this->total_rows * 100) : 0;
    }
}
