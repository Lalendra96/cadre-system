<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileCompletionTarget extends Model
{
    protected $fillable = [
        'user_id',
        'subject_code_id',
        'position_id',
        'target_count',
        'effective_from',
        'source_reference',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
        'disabled_by',
        'disabled_at',
        'disable_reason',
    ];

    protected $casts = [
        'target_count' => 'integer',
        'effective_from' => 'date',
        'is_active' => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subjectCode()
    {
        return $this->belongsTo(SubjectCode::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
