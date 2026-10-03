<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataQualityRule extends Model
{
    protected $fillable = ['code', 'label', 'severity', 'is_enabled', 'sort_order'];

    protected $casts = ['is_enabled' => 'boolean'];
}
