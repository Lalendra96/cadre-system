<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'users',
            function (Blueprint $table): void {
                $table->string(
                    'administrative_notice_acknowledged_version',
                    30
                )
                    ->nullable()
                    ->after('locale');

                $table->timestamp(
                    'administrative_notice_acknowledged_at'
                )
                    ->nullable()
                    ->after(
                        'administrative_notice_acknowledged_version'
                    );
            }
        );

        DB::table('system_settings')->updateOrInsert(
            [
                'key' => 'administrative_notice_version',
            ],
            [
                'value' => '1.0',
                'type' => 'string',
                'group' => 'governance',
                'label' => 'Administrative Notice Version',
                'description' => 'Version shown and acknowledged after each successful login.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where(
                'key',
                'administrative_notice_version'
            )
            ->delete();

        Schema::table(
            'users',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'administrative_notice_acknowledged_version',
                    'administrative_notice_acknowledged_at',
                ]);
            }
        );
    }
};
