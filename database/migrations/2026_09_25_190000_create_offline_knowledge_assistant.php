<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->string('title', 220);
            $table->string('source_type', 40);
            $table->string('language', 10)->default('en');
            $table->string('reference_no', 120)->nullable();
            $table->string('issuing_authority', 180)->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('classification', 30)->default('internal');
            $table->longText('content');
            $table->string('source_location', 255)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->foreignId('supersedes_id')->nullable()->constrained('ai_knowledge_sources');
            $table->timestamps();

            $table->index(['source_type', 'language', 'is_active']);
            $table->index(['is_verified', 'classification']);
        });

        Schema::create('ai_assistant_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedTinyInteger('phase');
            $table->text('question');
            $table->string('context_route', 180)->nullable();
            $table->string('answer_mode', 50);
            $table->json('source_ids')->nullable();
            $table->text('response_summary')->nullable();
            $table->boolean('support_only_acknowledged')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'phase', 'created_at']);
        });

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'feature_offline_kb_assistant'],
            [
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'label' => 'Offline Help & Knowledge Assistant',
                'description' => 'Enable governed offline application help, knowledge-base chat, context help and planning decision support.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')->updateOrInsert(
                ['route_name' => 'offline-assistant.index'],
                [
                    'section' => 'Help & Intelligence',
                    'label' => '🤖 Offline Help & KB Assistant',
                    'route_params' => null,
                    'open_in_new_tab' => false,
                    'allowed_roles' => json_encode([
                        'super_admin',
                        'admin_group',
                        'planning_officer',
                        'unit_manager',
                        'subject_officer',
                    ]),
                    'custom_checks' => null,
                    'sort_order' => 735,
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('nav_items')->updateOrInsert(
                ['route_name' => 'offline-assistant.sources.index'],
                [
                    'section' => 'Administration',
                    'label' => '📚 Assistant Knowledge Sources',
                    'route_params' => null,
                    'open_in_new_tab' => false,
                    'allowed_roles' => json_encode(['super_admin']),
                    'custom_checks' => null,
                    'sort_order' => 736,
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->seedHelpContent();
    }

    public function down(): void
    {
        if (Schema::hasTable('nav_items')) {
            DB::table('nav_items')->whereIn('route_name', [
                'offline-assistant.index',
                'offline-assistant.sources.index',
            ])->delete();
        }

        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->where('key', 'feature_offline_kb_assistant')->delete();
        }

        Schema::dropIfExists('ai_assistant_interactions');
        Schema::dropIfExists('ai_knowledge_sources');
    }

    private function seedHelpContent(): void
    {
        $rows = [
            [
                'title' => 'Application Help — Hospital Planning',
                'source_type' => 'application_help',
                'language' => 'en',
                'reference_no' => 'APP-HELP-PLANNING-01',
                'issuing_authority' => 'Carder Management System',
                'classification' => 'internal',
                'is_verified' => true,
                'content' => implode("\n\n", [
                    'Hospital Planning Assessment is a decision-support workspace. Use it to define the planning question, ' .
                        'record evidence and assumptions, compare reasonable alternatives, document workforce/financial/service ' .
                        'impacts and risks, and prepare an advisory recommendation.',
                    'Ready for Review means the assessment has completed the safeguard checks and is ready for an authorised ' .
                        'human reviewer. Referred does not mean approved. Recruitment, procurement, expenditure, establishment ' .
                        'changes and service reconfiguration require the competent authority and applicable official process.',
                ]),
            ],
            [
                'title' => 'Application Help — Hospital Planning (Sinhala)',
                'source_type' => 'application_help',
                'language' => 'si',
                'reference_no' => 'APP-HELP-PLANNING-SI-01',
                'issuing_authority' => 'Carder Management System',
                'classification' => 'internal',
                'is_verified' => true,
                'content' => "Hospital Planning Assessment යනු තීරණ සහාය සඳහා වන වැඩපිළිවෙළකි. සැලසුම් ප්‍රශ්නය, සාක්ෂි, උපකල්පන, විකල්ප, සේවා/කාර්ය මණ්ඩල/මූල්‍ය බලපෑම් සහ අවදානම් සටහන් කර නිර්දේශයක් සකස් කරන්න.\n\nReady for Review යන්නෙන් අනුමැතියක් අදහස් නොවේ. Referred තත්ත්වයද අවසාන අනුමැතියක් නොවේ. නිල තීරණය අදාළ බලධාරියා විසින් නිසි ක්‍රියාවලියෙන් ගත යුතුය.",
            ],
            [
                'title' => 'Application Help — Hospital Planning (Tamil)',
                'source_type' => 'application_help',
                'language' => 'ta',
                'reference_no' => 'APP-HELP-PLANNING-TA-01',
                'issuing_authority' => 'Carder Management System',
                'classification' => 'internal',
                'is_verified' => true,
                'content' => "Hospital Planning Assessment என்பது முடிவு-ஆதரவு பணிப்பரப்பு. திட்டமிடல் கேள்வி, ஆதாரங்கள், கருதுகோள்கள், மாற்று விருப்பங்கள், பணியாளர்/நிதி/சேவை தாக்கங்கள் மற்றும் அபாயங்களை பதிவு செய்து ஆலோசனை பரிந்துரையைத் தயாரிக்கவும்.\n\nReady for Review என்பது அனுமதி அல்ல. Referred என்பதும் இறுதி ஒப்புதல் அல்ல. அதிகாரப்பூர்வ முடிவு உரிய அதிகாரியால் நடைமுறைப்படி எடுக்கப்பட வேண்டும்.",
            ],
            [
                'title' => 'Application Help — Utility Bill Monitoring',
                'source_type' => 'application_help',
                'language' => 'en',
                'reference_no' => 'APP-HELP-UTILITY-01',
                'issuing_authority' => 'Carder Management System',
                'classification' => 'internal',
                'is_verified' => true,
                'content' => "Use Utility Bill Management to register utility accounts, capture bills, monitor due dates and record payment evidence. Verify the bill source, account number, billing period and payment reference before saving. The module monitors obligations and transactions; it does not itself provide expenditure or payment authority. Admin analytics are oversight indicators and should be checked against source records.",
            ],
            [
                'title' => 'Application Help — Retirement Projection',
                'source_type' => 'user_manual',
                'language' => 'en',
                'reference_no' => 'USER-MANUAL-RETIREMENT-01',
                'issuing_authority' => 'Carder Management System',
                'classification' => 'internal',
                'is_verified' => true,
                'content' => "To prepare a retirement projection, open Planning or Reports and select Retirement Projection. Choose the projection period, review the aggregate retirement counts and position impact, then use the output as planning evidence. Verify employee date-of-birth and service data through the authorised HR record process before any individual retirement action. The projection itself does not create a retirement decision.",
            ],
        ];

        foreach ($rows as $row) {
            DB::table('ai_knowledge_sources')->insert(array_merge($row, [
                'effective_date' => now()->toDateString(),
                'expiry_date' => null,
                'source_location' => 'Built-in application help',
                'verified_by' => null,
                'verified_at' => now(),
                'uploaded_by' => null,
                'supersedes_id' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
