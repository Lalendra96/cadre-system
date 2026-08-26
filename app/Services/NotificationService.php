<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * Central place to create in-app notifications. Keep this the ONLY writer
 * of the notifications table so every call site follows the same rules.
 *
 * DATA SECURITY RULE (read this before calling send() from a new call site)
 * ───────────────────────────────────────────────────────────────────────
 * A notification's $title/$body/$linkUrl must only contain information the
 * RECIPIENT is already independently authorised to see once they click
 * through — the notification is a pointer, never a shortcut around the
 * normal role/ownership check on the underlying route. For example:
 *   - "Your March 2026 entry for EA was verified" -> fine, the recipient
 *     is the officer who submitted it.
 *   - Do NOT put another subject officer's name, another unit's headcount,
 *     or any figure the recipient's role wouldn't otherwise see.
 *
 * USAGE
 * ─────
 *   NotificationService::send(
 *       user:  $entry->submittedBy,
 *       type:  'entry_verified',
 *       title: 'Entry verified',
 *       body:  "Your {$monthLabel} {$entry->year} entry for {$entry->subjectCode->code} was verified.",
 *       link:  route('carder-entries.index'),
 *   );
 *
 *   // Notify several recipients at once (e.g. letter recipients):
 *   NotificationService::sendToMany($recipientUsers, 'letter_received', $title, $body, $link);
 */
class NotificationService
{
    public static function send(
        ?User   $user,
        string  $type,
        string  $title,
        ?string $body = null,
        ?string $link = null,
        ?array  $data = null,
    ): ?Notification {
        // A null recipient (e.g. no Planning Officer configured yet) is a
        // silent no-op, not an error — callers should not have to guard
        // against every possible missing-user edge case themselves.
        if ($user === null) {
            return null;
        }

        return Notification::create([
            'user_id'  => $user->id,
            'type'     => $type,
            'title'    => $title,
            'body'     => $body,
            'link_url' => $link,
            'data'     => $data,
        ]);
    }

    /**
     * @param  iterable<User>  $users
     * @return Notification[]
     */
    public static function sendToMany(
        iterable $users,
        string   $type,
        string   $title,
        ?string  $body = null,
        ?string  $link = null,
        ?array   $data = null,
    ): array {
        $created = [];
        foreach ($users as $user) {
            $n = self::send($user, $type, $title, $body, $link, $data);
            if ($n !== null) {
                $created[] = $n;
            }
        }
        return $created;
    }
}
