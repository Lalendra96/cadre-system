<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class OfflineMfaService
{
    public static function generateSecret(): string
    {
        return bin2hex(
            random_bytes(20)
        );
    }

    public static function code(
        string $secret,
        ?int $time = null
    ): string {
        $counter = intdiv(
            $time ?? time(),
            30
        );

        $key = hex2bin($secret);
        $binary = pack('N*', 0)
            .pack('N*', $counter);

        $hash = hash_hmac(
            'sha1',
            $binary,
            $key,
            true
        );

        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad(
            (string) ($value % 1000000),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    public static function verify(
        User $user,
        string $code
    ): bool {
        if (! $user->mfa_enabled || ! $user->mfa_secret) {
            return true;
        }

        foreach ([-1, 0, 1] as $window) {
            if (hash_equals(
                self::code(
                    $user->mfa_secret,
                    time() + ($window * 30)
                ),
                trim($code)
            )) {
                return true;
            }
        }

        return false;
    }
}
