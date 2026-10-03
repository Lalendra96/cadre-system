<?php

namespace App\Services;

use Illuminate\Support\Str;

class ScreenHelpService
{
    public static function forRoute(?string $route): ?array
    {
        foreach (config('section_help', []) as $key => $topic) {
            if (Str::is($topic['routes'], $route ?? '')) {
                return $topic + ['key' => $key];
            }
        }

        return null;
    }
}
