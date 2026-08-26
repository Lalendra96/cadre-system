<?php

namespace App\Models;

use App\Traits\HasDisableWorkflow;
use Illuminate\Database\Eloquent\Model;

class IpAllowlist extends Model
{
    use HasDisableWorkflow;

    protected $table    = 'ip_allowlist';

    protected $fillable = [
        'cidr', 'description', 'is_active', 'created_by',
        'disabled_by', 'disabled_at', 'disable_reason',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'disabled_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── CIDR matching ──────────────────────────────────────────────────────

    public function matches(string $ip): bool
    {
        return (bool) @inet_pton($ip) && static::cidrContains($this->cidr, $ip);
    }

    public static function cidrContains(string $cidr, string $ip): bool
    {
        if (! str_contains($cidr, '/')) {
            return $cidr === $ip;
        }

        [$subnet, $prefix] = explode('/', $cidr, 2);
        $ipBin     = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $bits    = strlen($ipBin) * 8;
        $mask    = str_repeat('1', (int) $prefix) . str_repeat('0', $bits - (int) $prefix);
        $maskBin = pack('H*', implode('', array_map(
            fn ($c) => sprintf('%02x', bindec($c)),
            str_split($mask, 8)
        )));

        return ($ipBin & $maskBin) === ($subnetBin & $maskBin);
    }

    /** Check an IP against all active allowlist entries. */
    public static function allows(string $ip): bool
    {
        foreach (static::active()->get() as $entry) {
            if ($entry->matches($ip)) {
                return true;
            }
        }

        return false;
    }
}
