<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * circular_sends — one row per "send this circular to these groups"
 * action, tracking the outcome for accountability. A Circular can be
 * sent more than once (e.g. re-sent to a newly added group later), so
 * this is a history table, not fields directly on circulars.
 *
 * DATA SECURITY: recipient EMAIL ADDRESSES are deliberately NOT stored
 * here — only the count of successful/failed sends and which
 * position_groups were targeted. The actual recipient list can always be
 * re-derived on demand from Employee records (subject_code + position),
 * so persisting a second copy of employee email addresses in a send-log
 * table would just be an unnecessary additional place that data lives.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circular_sends', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('circular_id');
            $table->foreign('circular_id')->references('id')->on('circulars')->cascadeOnDelete();

            $table->unsignedBigInteger('sent_by');
            $table->foreign('sent_by')->references('id')->on('users')->restrictOnDelete();

            $table->json('position_group_ids'); // snapshot of which groups were targeted, in case a group is later edited/deleted
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_no_email_count')->default(0);

            $table->timestamps();
        });

        Schema::table('circulars', function (Blueprint $table) {
            $table->unsignedInteger('total_sends')->default(0)->after('view_count');
        });
    }

    public function down(): void
    {
        Schema::table('circulars', function (Blueprint $table) {
            $table->dropColumn('total_sends');
        });
        Schema::dropIfExists('circular_sends');
    }
};
