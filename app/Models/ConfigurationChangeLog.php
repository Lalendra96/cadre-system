<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigurationChangeLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'old_value',
        'new_value',
        'changed_by',
        'ip_address',
        'user_agent',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function changedBy()
    {
        return $this->belongsTo(
            User::class,
            'changed_by'
        );
    }
}
