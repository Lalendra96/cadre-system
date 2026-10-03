<?php

declare(strict_types=1);

namespace App\Services;

/** Pure policy shared by reconciliation, stale-case guards and regression checks. */
class HrOwnershipPolicy
{
    public static function state(?int $positionId, bool $active, array $owners): array
    {
        $positionId = $positionId !== null && $positionId > 0 ? $positionId : null;
        $owners = array_values(array_unique(array_filter(array_map('intval', $owners), fn (int $id) => $id > 0)));
        sort($owners, SORT_NUMERIC);

        return [
            'position_id' => $positionId,
            'active' => $active,
            'owner_ids' => $active && $positionId !== null ? $owners : [],
        ];
    }

    public static function fingerprint(array $state): string
    {
        return hash('sha256', json_encode($state, JSON_THROW_ON_ERROR));
    }

    public static function status(array $state): string
    {
        if (! $state['active']) {
            return 'inactive';
        }

        return match (count($state['owner_ids'])) {
            0 => 'unassigned',
            1 => 'awaiting_acceptance',
            default => 'needs_review',
        };
    }

    public static function needsCase(?array $before, array $after): bool
    {
        if (! $after['active']) {
            return false;
        }
        if ($before === null) {
            return count($after['owner_ids']) !== 1;
        }

        return self::fingerprint($before) !== self::fingerprint($after);
    }
}
