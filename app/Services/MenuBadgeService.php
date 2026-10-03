<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class MenuBadgeService
{
    /**
     * Return sidebar badge counts for the authenticated user.
     * Counts are intentionally user-scoped and unread-only.
     */
    public static function forUser(User $user): array
    {
        if (! Schema::hasTable('notifications')) {
            return [];
        }

        $base = Notification::forUser($user->id)->unread();

        $counts = [
            'notifications.index' => (clone $base)->count(),
            'service-letters.index' => (clone $base)->whereIn('type', [
                'service_letter_pending',
                'service_letter_approval_request',
            ])->count(),
            'cadre-reviews.index' => (clone $base)->whereIn('type', [
                'cadre_review_approval_request',
                'cadre_review_pending',
            ])->count(),
            'car-passes.index' => (clone $base)->whereIn('type', [
                'car_pass_approval_request',
                'car_pass_pending',
            ])->count(),
            'letters.index' => (clone $base)->whereIn('type', [
                'letter_received',
                'letter_review_requested',
            ])->count(),
            'hr-intelligence.index' => (clone $base)->where(function ($query) {
                $query->where('type', 'like', 'hr_%')
                    ->orWhere('type', 'like', 'reassignment_%')
                    ->orWhere('type', 'like', 'handover_%');
            })->count(),
        ];

        // The approval inbox is an actionable count rather than a notification count.
        $counts['approval-requests.index'] = ApprovalRequestService::countFor($user);

        return $counts;
    }
}
