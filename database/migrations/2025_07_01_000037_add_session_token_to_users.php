<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Stores the current valid session ID. On new login this is
            // updated; the ConcurrentSessionMiddleware rejects any request
            // whose session ID no longer matches.
            $table->string('current_session_token', 100)->nullable()->after('remember_token');
            $table->index('current_session_token', 'idx_users_session_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_session_token');
            $table->dropColumn('current_session_token');
        });
    }
};
