<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key-value store for super-admin configurable settings.
 *
 * Usage:
 *   SystemSetting::get('deadline_day', 15)
 *   SystemSetting::set('deadline_day', 20)
 *   SystemSetting::getInt('deadline_day', 15)
 *   SystemSetting::getBool('entry_verification_required', false)
 */
class SystemSetting extends Model
{
    protected $table      = 'system_settings';
    protected $primaryKey = 'key';
    public    $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable  = ['key','value','type','group','label','description'];
    protected $casts     = ['updated_at' => 'datetime'];

    private static array $_cache = [];

    // ── Read ────────────────────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$_cache)) {
            return static::$_cache[$key];
        }

        $row = static::find($key);
        if (! $row) {
            return $default;
        }

        $value = static::cast($row->value, $row->type);
        static::$_cache[$key] = $value;
        return $value;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::get($key, $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = static::get($key, $default);
        if (is_bool($val)) return $val;
        return in_array(strtolower((string) $val), ['1','true','yes','on'], true);
    }

    public static function getJson(string $key, mixed $default = null): mixed
    {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        // cast() in get() already calls json_decode() for type='json' rows,
        // so the value arrives here as an array. Return it directly to avoid
        // passing an array to json_decode() (which throws TypeError in PHP 8+).
        if (is_array($value) || is_object($value)) {
            return $value;
        }

        // Fallback for rows where type was not set to 'json' in the DB but
        // the stored value is a JSON string (defensive).
        if (! is_string($value)) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }

    // ── Write ────────────────────────────────────────────────────────

    public static function set(string $key, mixed $value): void
    {
        $stored = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        static::updateOrCreate(['key' => $key], ['value' => $stored]);
        unset(static::$_cache[$key]);
    }

    public static function setMany(array $map): void
    {
        foreach ($map as $key => $value) {
            static::set($key, $value);
        }
    }

    // ── Group fetch ──────────────────────────────────────────────────

    public static function group(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('group', $group)->orderBy('key')->get();
    }

    // ── Internal cast ────────────────────────────────────────────────

    private static function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => in_array(strtolower((string) $value), ['1','true','yes','on'], true),
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }
}
