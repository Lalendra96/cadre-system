<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Minimal live-notification endpoints, driven by client-side polling
 * (see resources/views/layouts/app.blade.php for the JS). No websocket
 * server or queue worker required — safe for a hospital LAN deployment
 * with no persistent Node/Redis process.
 *
 * DATA SECURITY
 * ─────────────
 * Every method here chains ->forUser(auth()->id()) before anything else.
 * This is deliberate and non-negotiable: without it, a user could pass
 * another user's notification ID and read or mark-as-read something that
 * was never sent to them (IDOR). Do not "optimise" this away.
 */
class NotificationController extends Controller
{
    /**
     * Lightweight polling endpoint — called every ~30s by the header bell.
     * Returns only a count, kept as small/fast as possible since it is
     * hit far more often than the full list.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Full list for the dropdown panel — most recent 20, read or unread.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::forUser($request->user()->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'type', 'title', 'body', 'link_url', 'is_read', 'created_at']);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => Notification::forUser($request->user()->id)->unread()->count(),
        ]);
    }

    /**
     * Mark a single notification read (called when the user clicks it).
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        // Ownership check — 404 rather than 403 so we don't confirm to a
        // curious client that a notification with that ID exists at all.
        abort_unless($notification->user_id === $request->user()->id, 404);

        $notification->markRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark every unread notification for this user as read (the "clear all" button).
     */
    public function markAllRead(Request $request): JsonResponse
    {
        Notification::forUser($request->user()->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
