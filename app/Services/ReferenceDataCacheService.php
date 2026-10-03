<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NavItem;
use App\Models\Position;
use App\Models\PublicHoliday;
use App\Models\Unit;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Short-lived cache for non-critical, read-heavy reference data.
 *
 * Governance boundary:
 * - No PII, payroll values, approvals, attendance punches, audit records,
 *   authentication state or authorization decisions are cached here.
 * - Cached records are versioned. A model change increments the namespace
 *   version, making stale entries unreachable immediately on the next read.
 * - TTL remains deliberately short as a second safety boundary.
 */
final class ReferenceDataCacheService
{
    private const TTL_NAV_SECONDS = 300;
    private const TTL_REFERENCE_SECONDS = 600;
    private const TTL_HOLIDAY_SECONDS = 900;

    public static function activeNavigation(): Collection
    {
        return Cache::remember(
            self::key('navigation', 'active'),
            now()->addSeconds(self::TTL_NAV_SECONDS),
            fn () => NavItem::query()
                ->active()
                ->orderBy('section')
                ->orderBy('sort_order')
                ->get()
        );
    }

    public static function activeUnits(): Collection
    {
        return Cache::remember(
            self::key('units', 'active'),
            now()->addSeconds(self::TTL_REFERENCE_SECONDS),
            fn () => Unit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
        );
    }

    public static function activePositions(): Collection
    {
        return Cache::remember(
            self::key('positions', 'active'),
            now()->addSeconds(self::TTL_REFERENCE_SECONDS),
            fn () => Position::query()
                ->where('is_active', true)
                ->orderBy('title')
                ->get()
        );
    }

    public static function publicHolidayOn(CarbonInterface|string $date): ?PublicHoliday
    {
        $dateString = $date instanceof CarbonInterface
            ? $date->toDateString()
            : (string) $date;

        return Cache::remember(
            self::key('public_holidays', $dateString),
            now()->addSeconds(self::TTL_HOLIDAY_SECONDS),
            fn () => PublicHoliday::query()
                ->whereDate('holiday_date', $dateString)
                ->where('is_active', true)
                ->first()
        );
    }

    public static function invalidateNavigation(): void
    {
        self::bump('navigation');
    }

    public static function invalidateUnits(): void
    {
        self::bump('units');
    }

    public static function invalidatePositions(): void
    {
        self::bump('positions');
    }

    public static function invalidatePublicHolidays(): void
    {
        self::bump('public_holidays');
    }

    private static function key(string $namespace, string $suffix): string
    {
        return sprintf(
            'carder:ref:%s:v%d:%s',
            $namespace,
            self::version($namespace),
            $suffix
        );
    }

    private static function version(string $namespace): int
    {
        $key = 'carder:ref-version:'.$namespace;

        return (int) Cache::rememberForever($key, static fn () => 1);
    }

    private static function bump(string $namespace): void
    {
        $key = 'carder:ref-version:'.$namespace;

        // Ensure the key exists for cache stores where increment requires it.
        Cache::add($key, 1);
        Cache::increment($key);
    }
}
