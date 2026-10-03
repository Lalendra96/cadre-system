<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class HrReminderService
{
    /** Unique delivery and notification commit together, including concurrent runs. */
    public static function deliver(
        User $user,
        string $eventKey,
        string $type,
        string $title,
        string $body,
        string $link,
    ): bool {
        return DB::transaction(function () use ($user, $eventKey, $type, $title, $body, $link) {
            $inserted = DB::table('hr_reminder_deliveries')->insertOrIgnore([
                'event_key' => $eventKey,
                'user_id' => $user->id,
                'created_at' => now(),
            ]);
            if (! $inserted) {
                return false;
            }
            NotificationService::send($user, $type, $title, $body, $link);

            return true;
        });
    }

    public static function markers(array $configured): array
    {
        $valid = array_filter($configured, static function ($value): bool {
            $days = filter_var($value, FILTER_VALIDATE_INT);

            return $days !== false && $days >= 0 && $days <= 3650;
        });
        $markers = array_values(array_unique(array_map('intval', $valid)));
        sort($markers);

        return $markers;
    }

    /** Select only the current reminder band, so missed jobs do not flood users. */
    public static function markerFor(int $daysLeft, array $markers): ?int
    {
        if ($daysLeft < 0) {
            return null;
        }
        foreach ($markers as $marker) {
            if ($daysLeft <= $marker) {
                return $marker;
            }
        }

        return null;
    }
}
