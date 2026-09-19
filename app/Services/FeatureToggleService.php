<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;

class FeatureToggleService
{
    public const FEATURES = [
        'service_letters' => [
            'key' => 'feature_service_letters',
            'label' => '✉️ Service Letters + Letterheads',
            'default' => true,
        ],
        'trilingual_ui' => [
            'key' => 'feature_trilingual_ui',
            'label' => '🌐 Trilingual Interface',
            'default' => true,
        ],
        'service_history' => [
            'key' => 'feature_service_history',
            'label' => '📂 Service History / Combined Service',
            'default' => true,
        ],
        'grade_progression' => [
            'key' => 'feature_grade_progression',
            'label' => '🎖 Grade Progression',
            'default' => true,
        ],
        'ai_record_assistant' => [
            'key' => 'feature_ai_record_assistant',
            'label' => '✨ AI Record Assistant',
            'default' => true,
        ],
        'ai_service_letter_assistant' => [
            'key' => 'feature_ai_service_letter_assistant',
            'label' => '✨ AI Service Letter Assistant',
            'default' => true,
        ],
    ];

    public static function enabled(string $feature): bool
    {
        $config = self::FEATURES[$feature] ?? null;

        if ($config === null) {
            return false;
        }

        return SystemSetting::getBool(
            $config['key'],
            $config['default']
        );
    }

    public static function routeEnabled(string $routeName): bool
    {
        return match (true) {
            str_starts_with($routeName, 'service-letters.'),
            str_starts_with($routeName, 'service-letter-templates.'),
            str_starts_with($routeName, 'service-letter-letterheads.')
                => self::enabled('service_letters'),

            str_starts_with($routeName, 'employee-service-periods.'),
            str_starts_with($routeName, 'incoming-officers.')
                => self::enabled('service_history'),

            str_starts_with($routeName, 'employee-grades.')
                => self::enabled('grade_progression'),

            str_starts_with($routeName, 'ai-record-assistant.')
                => self::enabled('ai_record_assistant'),

            default => true,
        };
    }
}
