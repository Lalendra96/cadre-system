<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notifications
 * ─────────────
 * A minimal, self-contained in-app notification store. Deliberately NOT
 * using Laravel's built-in Notifiable/DatabaseNotification system — that
 * uses a polymorphic notifiable_type/notifiable_id pair and a UUID key,
 * which is more machinery than this system needs (every notification here
 * always targets exactly one App\Models\User). A plain user_id foreign key
 * keeps the schema simple, indexable, and easy to reason about for a
 * security review.
 *
 * DATA SECURITY
 * ─────────────
 * `link_url` and `title`/`body` must never contain data the recipient is
 * not already independently authorised to see — a notification is a
 * pointer/summary, not a bypass of the existing role/ownership checks on
 * the underlying resource. See NotificationService docblock for the rule
 * this is enforced by at the call site.
 *
 * Every query against this table MUST be scoped to the authenticated
 * user's own user_id (see Notification::scopeForUser()) to prevent one
 * user from reading or marking another user's notifications (IDOR).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();

            // Short machine-readable category, e.g. 'letter_received',
            // 'entry_verified', 'entry_rejected', 'amendment_approved'.
            // Used for icon/colour selection in the UI — not for access control.
            $table->string('type', 60);

            $table->string('title', 150);
            $table->string('body', 300)->nullable();

            // Where clicking the notification should take the user.
            // Must always be a route the recipient is already authorised
            // to view under normal navigation — never a signed/bypass URL.
            $table->string('link_url', 255)->nullable();

            // Optional small structured payload (e.g. ['entry_id' => 42])
            // for future use by the frontend; not required for v1.
            $table->json('data')->nullable();

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Every list/count query filters by (user_id, is_read) — index both
            // access patterns: "all for user, newest first" and "unread count".
            $table->index(['user_id', 'created_at'], 'idx_notifications_user_created');
            $table->index(['user_id', 'is_read'], 'idx_notifications_user_unread');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
