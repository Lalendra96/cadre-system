<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemSetting extends Model
{
    protected $table = 'system_settings';
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    private static array $runtimeCache = [];

    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        if (array_key_exists($key, static::$runtimeCache)) {
            return static::$runtimeCache[$key];
        }

        $row = static::find($key);

        if (! $row) {
            return $default;
        }

        $value = static::cast(
            $row->value,
            $row->type
        );

        static::$runtimeCache[$key] = $value;

        return $value;
    }

    public static function getInt(
        string $key,
        int $default = 0
    ): int {
        return (int) static::get(
            $key,
            $default
        );
    }

    public static function getBool(
        string $key,
        bool $default = false
    ): bool {
        $value = static::get(
            $key,
            $default
        );

        if (is_bool($value)) {
            return $value;
        }

        return in_array(
            strtolower((string) $value),
            ['1', 'true', 'yes', 'on'],
            true
        );
    }

    public static function getJson(
        string $key,
        mixed $default = null
    ): mixed {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        if (is_array($value) || is_object($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return $default;
        }

        $decoded = json_decode(
            $value,
            true
        );

        return json_last_error() === JSON_ERROR_NONE
            ? $decoded
            : $default;
    }

    public static function set(
        string $key,
        mixed $value
    ): void {
        $stored = is_bool($value)
            ? ($value ? 'true' : 'false')
            : (string) $value;

        $row = static::find($key);
        $oldValue = $row?->value;

        if ($oldValue === $stored) {
            return;
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored]
        );

        unset(static::$runtimeCache[$key]);

        static::auditConfigurationChange(
            $key,
            $oldValue,
            $stored
        );
    }

    public static function setMany(array $map): void
    {
        foreach ($map as $key => $value) {
            static::set(
                $key,
                $value
            );
        }
    }

    public static function group(
        string $group
    ): \Illuminate\Database\Eloquent\Collection {
        return static::where(
            'group',
            $group
        )
            ->orderBy('key')
            ->get();
    }

    private static function auditConfigurationChange(
        string $key,
        ?string $oldValue,
        ?string $newValue
    ): void {
        if (! Schema::hasTable('configuration_change_logs')) {
            return;
        }

        DB::table('configuration_change_logs')->insert([
            'setting_key' => $key,
            'old_value' => static::redactForAudit(
                $key,
                $oldValue
            ),
            'new_value' => static::redactForAudit(
                $key,
                $newValue
            ),
            'changed_by' => auth()->id(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()
                ? substr(
                    (string) request()->userAgent(),
                    0,
                    255
                )
                : null,
            'changed_at' => now(),
        ]);
    }

    private static function redactForAudit(
        string $key,
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (preg_match(
            '/password|secret|token|credential|api[_-]?key/i',
            $key
        )) {
            return '[REDACTED]';
        }

        return $value;
    }

    private static function cast(
        mixed $value,
        string $type
    ): mixed {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => in_array(
                strtolower((string) $value),
                ['1', 'true', 'yes', 'on'],
                true
            ),
            'json' => json_decode(
                $value,
                true
            ),
            default => $value,
        };
    }
}
