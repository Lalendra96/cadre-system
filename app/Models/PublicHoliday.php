<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\ReferenceDataCacheService;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    protected $fillable = [
        'holiday_date',
        'name',
        'holiday_type',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(static fn () => ReferenceDataCacheService::invalidatePublicHolidays());
        static::deleted(static fn () => ReferenceDataCacheService::invalidatePublicHolidays());
    }
}
