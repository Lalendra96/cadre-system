<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the single users.subject_code_id FK with the subject_code_user
 * pivot (a Subject Officer can hold multiple subject codes). Any existing
 * single-assignment data is copied into the pivot table before the column
 * is dropped, so nothing already entered is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Carry forward any existing single-assignment data into the pivot table.
        if (Schema::hasColumn('users', 'subject_code_id')) {
            $existing = DB::table('users')->whereNotNull('subject_code_id')->get(['id', 'subject_code_id']);

            foreach ($existing as $row) {
                DB::table('subject_code_user')->insertOrIgnore([
                    'user_id'         => $row->id,
                    'subject_code_id' => $row->subject_code_id,
                    'assigned_at'     => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['subject_code_id']);
                $table->dropColumn('subject_code_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_code_id')->nullable()->after('category_id');
            $table->foreign('subject_code_id')->references('id')->on('subject_codes')->nullOnDelete();
        });
    }
};
