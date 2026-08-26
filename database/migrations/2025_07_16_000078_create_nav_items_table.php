<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * nav_items — Super-Admin-configurable navigation, replacing the
 * previously hardcoded @if($user->isX()) chains in layouts/app.blade.php.
 *
 * VISIBILITY MODEL — three layers, all must pass:
 *   1. is_active (simple on/off)
 *   2. allowed_roles (JSON array of role strings, e.g. ["super_admin",
 *      "admin_group"]) — null/empty means "no role restriction" (still
 *      requires being logged in)
 *   3. custom_checks (JSON array of WHITELISTED check names — see
 *      NavItem::CUSTOM_CHECKS) — ALL listed checks must pass (AND logic).
 *      This is how the pre-existing custom-permission-method items
 *      (canVerifyEntries(), isExecutiveViewer(), isHrRecordsManager(),
 *      canManageCircularGroups(), the can_view_employees/can_view_letters
 *      flags, hasAnyPositionBoundCode()) are preserved — Super Admin can
 *      toggle these items on/off and reorder them, but cannot rewire
 *      WHICH business-logic method a "custom" item checks, since that
 *      would mean calling an arbitrary method name pulled from the
 *      database — a real security risk (method-injection style abuse).
 *      custom_checks values are validated against the fixed whitelist on
 *      every write, not just trusted at render time.
 *
 * `section` groups items under a header in the nav (e.g. "Reports",
 * "Carder", "My Work") — purely a display grouping, not itself a
 * permission gate; each item's own allowed_roles/custom_checks are what
 * actually control visibility.
 *
 * The same route can legitimately appear as multiple SEPARATE nav_items
 * rows (e.g. "Employee Profiles" shows under three different sections
 * for three different audiences with three different visibility rules
 * in the original hardcoded nav) — this is intentional, not a duplicate
 * to be merged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('section', 100)->nullable();
            $table->string('label', 150);
            $table->string('route_name', 150);
            $table->json('route_params')->nullable(); // rarely needed; null for parameterless routes
            $table->boolean('open_in_new_tab')->default(false);

            $table->json('allowed_roles')->nullable();   // ["super_admin","admin_group",...] or null = no role restriction
            $table->json('custom_checks')->nullable();   // ["canVerifyEntries",...] — whitelisted, ALL must pass

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_items');
    }
};
