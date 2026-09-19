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
            'business_rule_versions',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_rule_id')
                    ->constrained('business_rules')
                    ->cascadeOnDelete();
                $table->unsignedInteger('version_no');
                $table->string('change_type', 30);
                $table->json('snapshot');
                $table->foreignId('changed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->text('change_reason')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(
                    ['business_rule_id', 'version_no'],
                    'business_rule_versions_unique'
                );
            }
        );

        Schema::create(
            'configuration_change_logs',
            function (Blueprint $table): void {
                $table->id();
                $table->string('setting_key', 160);
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->foreignId('changed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->string('ip_address', 64)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('changed_at')->useCurrent();

                $table->index([
                    'setting_key',
                    'changed_at',
                ]);
            }
        );

        Schema::create(
            'administrative_decisions',
            function (Blueprint $table): void {
                $table->id();
                $table->string('decision_type', 80);
                $table->foreignId('employee_id')
                    ->nullable()
                    ->constrained('employees')
                    ->nullOnDelete();
                $table->string('summary', 500);
                $table->text('payload');
                $table->string('source_reference', 180)->nullable();
                $table->foreignId('business_rule_id')
                    ->nullable()
                    ->constrained('business_rules')
                    ->nullOnDelete();
                $table->string('status', 30)->default('pending');
                $table->foreignId('requested_by')
                    ->constrained('users');
                $table->timestamp('requested_at')->useCurrent();
                $table->foreignId('decided_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('decided_at')->nullable();
                $table->text('decision_reason')->nullable();
                $table->string('applied_model_type', 180)->nullable();
                $table->unsignedBigInteger('applied_model_id')->nullable();
                $table->timestamps();

                $table->index([
                    'status',
                    'decision_type',
                ]);
                $table->index([
                    'requested_by',
                    'requested_at',
                ]);
            }
        );

        Schema::create(
            'administrative_decision_events',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('administrative_decision_id')
                    ->constrained('administrative_decisions')
                    ->cascadeOnDelete();
                $table->string('event', 40);
                $table->foreignId('actor_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->text('reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
            }
        );

        $now = now();

        foreach ([
            'governance_profile_confirmed_by' => [
                '',
                'string',
                'Governance Profile Confirmed By',
            ],
            'governance_profile_confirmed_designation' => [
                '',
                'string',
                'Governance Profile Confirmer Designation',
            ],
            'governance_profile_confirmed_at' => [
                '',
                'string',
                'Governance Profile Confirmed At',
            ],
            'governance_profile_confirmation_reference' => [
                '',
                'string',
                'Governance Profile Confirmation Reference',
            ],
        ] as $key => [$value, $type, $label]) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                    'group' => 'governance',
                    'label' => $label,
                    'description' => 'Institutional confirmation record for the governance responsibility profile.',
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
                    'governance_profile_confirmed_by',
                    'governance_profile_confirmed_designation',
                    'governance_profile_confirmed_at',
                    'governance_profile_confirmation_reference',
                ]
            )
            ->delete();

        Schema::dropIfExists('administrative_decision_events');
        Schema::dropIfExists('administrative_decisions');
        Schema::dropIfExists('configuration_change_logs');
        Schema::dropIfExists('business_rule_versions');
    }
};
