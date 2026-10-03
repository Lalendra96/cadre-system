<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('administrative_decisions')) {
            Schema::table('administrative_decisions', function (Blueprint $table): void {
                if (! Schema::hasColumn('administrative_decisions', 'decision_no')) {
                    $table->string('decision_no', 80)->nullable()->unique();
                }
                if (! Schema::hasColumn('administrative_decisions', 'title')) {
                    $table->string('title', 200)->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'source_type')) {
                    $table->string('source_type', 100)->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'approval_authority_id')) {
                    $table->foreignId('approval_authority_id')->nullable()->constrained('approval_authorities')->nullOnDelete();
                }
                if (! Schema::hasColumn('administrative_decisions', 'rule_version_id')) {
                    $table->foreignId('rule_version_id')->nullable()->constrained('governance_rule_versions')->nullOnDelete();
                }
                if (! Schema::hasColumn('administrative_decisions', 'authority_reference')) {
                    $table->string('authority_reference', 160)->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'effective_date')) {
                    $table->date('effective_date')->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'decision_text')) {
                    $table->text('decision_text')->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'supporting_evidence')) {
                    $table->text('supporting_evidence')->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'provenance')) {
                    $table->jsonb('provenance')->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'sod_override')) {
                    $table->boolean('sod_override')->default(false);
                }
                if (! Schema::hasColumn('administrative_decisions', 'sod_override_reason')) {
                    $table->text('sod_override_reason')->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'sod_override_authority')) {
                    $table->string('sod_override_authority', 160)->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'prepared_by')) {
                    $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('administrative_decisions', 'checked_by')) {
                    $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('administrative_decisions', 'recommended_by')) {
                    $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('administrative_decisions', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('administrative_decisions', 'checked_at')) {
                    $table->timestamp('checked_at')->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'recommended_at')) {
                    $table->timestamp('recommended_at')->nullable();
                }
                if (! Schema::hasColumn('administrative_decisions', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('administrative_decisions') && Schema::hasColumn('administrative_decisions', 'decision_no')) {
            DB::table('administrative_decisions')->orderBy('id')->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $year = $row->requested_at ? substr((string) $row->requested_at, 0, 4) : now()->format('Y');
                    $update = [];
                    if (empty($row->decision_no)) {
                        $update['decision_no'] = 'ADM-'.$year.'-'.str_pad((string) $row->id, 6, '0', STR_PAD_LEFT);
                    }
                    if (empty($row->title)) {
                        $update['title'] = $row->summary ?: 'Administrative decision #'.$row->id;
                    }
                    if (empty($row->source_type)) {
                        $update['source_type'] = $row->decision_type;
                    }
                    if (empty($row->prepared_by)) {
                        $update['prepared_by'] = $row->requested_by;
                    }
                    if (empty($row->provenance)) {
                        $update['provenance'] = json_encode([
                            'migrated_legacy_decision' => true,
                            'source_reference' => $row->source_reference,
                            'method' => 'Governance metadata backfilled from the pre-existing administrative-decision record.',
                            'captured_at' => (string) ($row->requested_at ?: $row->created_at),
                        ]);
                    }
                    if ($update) {
                        DB::table('administrative_decisions')->where('id', $row->id)->update($update);
                    }
                }
            });
        }

        if (Schema::hasTable('employee_promotions')) {
            Schema::table('employee_promotions', function (Blueprint $table): void {
                if (! Schema::hasColumn('employee_promotions', 'recommended_by')) {
                    $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('employee_promotions', 'recommended_at')) {
                    $table->timestamp('recommended_at')->nullable();
                }
                if (! Schema::hasColumn('employee_promotions', 'administrative_decision_id')) {
                    $table->foreignId('administrative_decision_id')->nullable()->constrained('administrative_decisions')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('records_retention_cases') && ! Schema::hasColumn('records_retention_cases', 'disposed_by')) {
            Schema::table('records_retention_cases', function (Blueprint $table): void {
                $table->foreignId('disposed_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('handover_certificates')) {
            Schema::table('handover_certificates', function (Blueprint $table): void {
                if (! Schema::hasColumn('handover_certificates', 'from_e_signature_id')) {
                    $table->foreignId('from_e_signature_id')->nullable()->constrained('e_signatures')->nullOnDelete();
                }
                if (! Schema::hasColumn('handover_certificates', 'to_e_signature_id')) {
                    $table->foreignId('to_e_signature_id')->nullable()->constrained('e_signatures')->nullOnDelete();
                }
                if (! Schema::hasColumn('handover_certificates', 'supervisor_e_signature_id')) {
                    $table->foreignId('supervisor_e_signature_id')->nullable()->constrained('e_signatures')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('regulatory_changes')) {
            Schema::table('regulatory_changes', function (Blueprint $table): void {
                if (! Schema::hasColumn('regulatory_changes', 'circular_id')) {
                    $table->foreignId('circular_id')->nullable()->constrained('circulars')->nullOnDelete();
                }
                if (! Schema::hasColumn('regulatory_changes', 'affected_rule_version_ids')) {
                    $table->jsonb('affected_rule_version_ids')->nullable();
                }
                if (! Schema::hasColumn('regulatory_changes', 'implementation_tasks')) {
                    $table->jsonb('implementation_tasks')->nullable();
                }
                if (! Schema::hasColumn('regulatory_changes', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('regulatory_changes', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable();
                }
            });
        }

        foreach (['retirement_projects', 'cadre_review_proposals', 'employee_change_requests'] as $name) {
            if (Schema::hasTable($name) && ! Schema::hasColumn($name, 'administrative_decision_id')) {
                Schema::table($name, function (Blueprint $table): void {
                    $table->foreignId('administrative_decision_id')->nullable()->constrained('administrative_decisions')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: these columns preserve governance evidence.
    }
};
