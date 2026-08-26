<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->date('date_of_appointment')->nullable()->after('date_of_birth');
            $table->integer('retirement_age')->default(60)->after('date_of_appointment');
            $table->text('notes')->nullable()->after('retirement_age');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['date_of_birth','date_of_appointment','retirement_age','notes']);
        });
    }
};
