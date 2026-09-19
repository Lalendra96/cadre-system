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
        Schema::create(
            'business_rules',
            function (Blueprint $table): void {
                $table->id();
                $table->string('code', 80)->unique();
                $table->string('name', 160);
                $table->text('system_behavior');
                $table->string('authority_type', 60);
                $table->string('authority_reference', 180)->nullable();
                $table->string('authority_title', 300)->nullable();
                $table->string('authority_url', 500)->nullable();
                $table->date('effective_date')->nullable();
                $table->date('review_due_date')->nullable();
                $table->string('approved_by_name', 180)->nullable();
                $table->string('approved_by_designation', 180)->nullable();
                $table->string('approval_reference', 180)->nullable();
                $table->string('status', 30)->default('draft');
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index([
                    'status',
                    'is_active',
                ]);
            }
        );

        $now = now();

        $settings = [
            [
                'key' => 'governance_system_owner',
                'value' => 'Teaching Hospital Peradeniya',
                'type' => 'string',
                'group' => 'governance',
                'label' => 'System Owner',
                'description' => 'Institutional owner of the Carder Management deployment.',
            ],
            [
                'key' => 'governance_data_controller',
                'value' => 'To be confirmed by the authorised institution',
                'type' => 'string',
                'group' => 'governance',
                'label' => 'Data Controller',
                'description' => 'Record the legally/administratively confirmed controller designation.',
            ],
            [
                'key' => 'governance_decision_authority',
                'value' => 'To be confirmed by the authorised institution',
                'type' => 'string',
                'group' => 'governance',
                'label' => 'Administrative Decision Authority',
                'description' => 'Institutional authority responsible for approving administrative decisions and business rules.',
            ],
            [
                'key' => 'governance_technical_maintainer',
                'value' => 'Authorised software maintenance provider',
                'type' => 'string',
                'group' => 'governance',
                'label' => 'Technical Maintainer',
                'description' => 'Technical maintainer/service provider. This designation does not confer administrative decision authority.',
            ],
            [
                'key' => 'governance_contact',
                'value' => '',
                'type' => 'string',
                'group' => 'governance',
                'label' => 'Governance Contact',
                'description' => 'Institutional governance/legal/data-protection contact.',
            ],
            [
                'key' => 'governance_disclaimer_version',
                'value' => '1.0',
                'type' => 'string',
                'group' => 'governance',
                'label' => 'Decision-Support Notice Version',
                'description' => 'Version identifier for the current internal-use notice.',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                [
                    'key' => $setting['key'],
                ],
                $setting + [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn(
                'key',
                [
                    'governance_system_owner',
                    'governance_data_controller',
                    'governance_decision_authority',
                    'governance_technical_maintainer',
                    'governance_contact',
                    'governance_disclaimer_version',
                ]
            )
            ->delete();

        Schema::dropIfExists('business_rules');
    }
};
