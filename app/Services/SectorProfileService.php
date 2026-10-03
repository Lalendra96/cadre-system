<?php

namespace App\Services;

use App\Models\SystemSetting;

class SectorProfileService
{
    public static function edition(): string
    {
        return trim((string) SystemSetting::get('client_profile_edition', 'Government / Custom'));
    }

    public static function isPrivate(): bool
    {
        $edition = strtolower(self::edition());
        foreach (['private', 'gp', 'clinic', 'commercial'] as $needle) {
            if (str_contains($edition, $needle)) return true;
        }
        return false;
    }

    public static function isGovernment(): bool
    {
        return ! self::isPrivate();
    }
}
