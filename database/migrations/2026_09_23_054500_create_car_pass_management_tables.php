<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_pass_responsibility_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_officer_id')->constrained('users');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('reason');
            $table->string('reference_no', 100)->nullable();
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users');
            $table->text('end_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('car_pass_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50);
            $table->string('image_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->jsonb('allowed_position_ids');
            $table->unsignedSmallInteger('default_validity_months')->default(12);
            $table->unsignedInteger('version')->default(1);
            $table->unique(['code', 'version']);
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('disabled_by')->nullable()->constrained('users');
            $table->timestamp('disabled_at')->nullable();
            $table->text('disable_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('car_pass_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference_no', 60)->unique();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('template_id')->constrained('car_pass_templates');
            $table->string('employee_name_snapshot', 180);
            $table->string('pay_no_snapshot', 60)->nullable();
            $table->string('position_snapshot', 180)->nullable();
            $table->string('unit_snapshot', 180)->nullable();
            $table->string('template_name_snapshot', 150);
            $table->string('template_code_snapshot', 50);
            $table->unsignedInteger('template_version_snapshot');
            $table->string('template_image_snapshot_path', 500);
            $table->string('vehicle_registration_no', 40);
            $table->string('vehicle_type', 40);
            $table->date('valid_from');
            $table->date('valid_to');
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users');
            $table->text('revocation_reason')->nullable();
            $table->string('verification_token_hash', 128)->nullable();
            $table->string('content_hash', 128)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['status', 'submitted_at']);
            $table->index(['prepared_by', 'created_at']);
        });

        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => 'feature_car_passes'],
                [
                    'value' => 'false',
                    'type' => 'boolean',
                    'group' => 'features',
                    'label' => 'Car Pass Management',
                    'description' => 'Enable the governed Car Pass workflow.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('system_settings')->updateOrInsert(
                ['key' => 'car_pass_approval_role'],
                [
                    'value' => 'admin_group',
                    'type' => 'string',
                    'group' => 'car_passes',
                    'label' => 'Car Pass approval role',
                    'description' => 'Independent role authorised to approve Car Pass requests.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')->updateOrInsert(
                ['route_name' => 'car-passes.index'],
                [
                    'section' => 'My Work',
                    'label' => '🚗 Car Pass Management',
                    'open_in_new_tab' => false,
                    'allowed_roles' => json_encode(['subject_officer', 'admin_group', 'planning_officer', 'super_admin']),
                    'custom_checks' => null,
                    'sort_order' => 405,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('nav_items')->updateOrInsert(
                ['route_name' => 'car-passes.admin.index'],
                [
                    'section' => 'Administration',
                    'label' => '🚗 Car Pass Administration',
                    'open_in_new_tab' => false,
                    'allowed_roles' => json_encode(['super_admin']),
                    'custom_checks' => null,
                    'sort_order' => 445,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')->whereIn('route_name', ['car-passes.index', 'car-passes.admin.index'])->delete();
        }

        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->whereIn('key', ['feature_car_passes', 'car_pass_approval_role'])->delete();
        }

        Schema::dropIfExists('car_pass_requests');
        Schema::dropIfExists('car_pass_templates');
        Schema::dropIfExists('car_pass_responsibility_assignments');
    }
};
