<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class ServiceLetterTemplate extends Model
{
    use HasDisableWorkflow;

    public const LANGUAGES = ['si' => 'Sinhala', 'en' => 'English'];

    protected $fillable = [
        'name', 'language', 'body', 'description', 'created_by',
        'is_active', 'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function serviceLetters()
    {
        return $this->hasMany(ServiceLetter::class, 'template_id');
    }

    public function scopeLanguage($query, string $lang)
    {
        return $query->where('language', $lang);
    }
}
