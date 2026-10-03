<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeFieldProvenance;
use App\Models\ExternalHrReconciliationItem;
use App\Services\AdvancedGovernanceService;
use App\Services\PersonnelDisplayService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class AdvancedGovernanceController extends Controller
{
    public function __construct(private readonly AdvancedGovernanceService $service) {}

    public function index(Request $request)
    {
        $this->authorizeAccess($request);

        $asAt = Carbon::parse((string) $request->query('as_at', now()->toDateString()));
        $until = Carbon::parse((string) $request->query('until', now()->addYear()->toDateString()));

        if ($until->lt($asAt)) {
            throw ValidationException::withMessages([
                'until' => 'Forecast end date must be on or after the start date.',
            ]);
        }

        $metrics = [
            'rules' => $this->countTable('governance_rule_versions'),
            'findings' => $this->countTable('administrative_eligibility_findings'),
            'reconciliation' => Schema::hasTable('external_hr_reconciliation_items')
                ? DB::table('external_hr_reconciliation_items')->where('status', 'review')->count()
                : 0,
            'missing_documents' => $this->missingDocumentCount(),
            'provenance' => $this->countTable('employee_field_provenance'),
            'case_bundles' => $this->countTable('formal_case_bundles'),
        ];

        $forecast = $this->service->forecast($asAt, $until);
        $snapshot = $this->service->temporalSnapshot($asAt);
        $missingDocuments = $this->service->missingDocumentIntelligence();
        $sources = Schema::hasTable('external_hr_sources')
            ? DB::table('external_hr_sources')->where('is_active', true)->orderBy('name')->get()
            : collect();
        $reconciliationItems = Schema::hasTable('external_hr_reconciliation_items')
            ? ExternalHrReconciliationItem::query()
                ->whereIn('status', ['review', 'deferred'])
                ->orderByDesc('id')
                ->limit(30)
                ->get()
            : collect();
        $reconciliationItems = PersonnelDisplayService::hydrateEmployeeRows($reconciliationItems);
        $bundles = Schema::hasTable('formal_case_bundles')
            ? DB::table('formal_case_bundles')->orderByDesc('generated_at')->limit(12)->get()
            : collect();
        $exports = Schema::hasTable('assurance_exports')
            ? DB::table('assurance_exports')->orderByDesc('generated_at')->limit(12)->get()
            : collect();

        $findings = Schema::hasTable('administrative_eligibility_findings')
            ? DB::table('administrative_eligibility_findings as f')
                ->join('employees as e', 'e.id', '=', 'f.employee_id')
                ->select('f.*')
                ->orderByRaw("CASE WHEN f.status IN ('overdue','due') THEN 0 ELSE 1 END")
                ->orderBy('f.target_date')
                ->limit(30)
                ->get()
            : collect();
        $findings = PersonnelDisplayService::hydrateEmployeeRows($findings);

        return view('advanced-governance.index', compact(
            'metrics',
            'forecast',
            'snapshot',
            'findings',
            'missingDocuments',
            'sources',
            'reconciliationItems',
            'bundles',
            'exports',
            'asAt',
            'until'
        ));
    }

    public function refreshEligibility(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);
        $count = $this->service->refreshEligibility();

        return back()->with('success', "Eligibility intelligence refreshed: {$count} findings evaluated. No administrative decisions were made automatically.");
    }

    public function storeProvision(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'rule_version_id' => ['required', 'integer', 'exists:governance_rule_versions,id'],
            'provision_code' => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9._-]+$/'],
            'subject_area' => ['required', Rule::in(['confirmation', 'increment', 'efficiency_bar', 'promotion', 'retirement', 'registration', 'transfer', 'cadre', 'other'])],
            'conditions' => ['required', 'json'],
            'outcomes' => ['required', 'json'],
            'required_evidence' => ['required', 'json'],
            'plain_language_summary' => ['nullable', 'string', 'max:3000'],
        ]);

        $evidence = json_decode($data['required_evidence'], true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($evidence)) {
            throw ValidationException::withMessages(['required_evidence' => 'Required evidence must be a JSON array.']);
        }

        DB::table('governance_rule_provisions')->updateOrInsert(
            [
                'rule_version_id' => $data['rule_version_id'],
                'provision_code' => $data['provision_code'],
            ],
            [
                'subject_area' => $data['subject_area'],
                'conditions' => $data['conditions'],
                'outcomes' => $data['outcomes'],
                'required_evidence' => $data['required_evidence'],
                'requires_human_decision' => true,
                'plain_language_summary' => $data['plain_language_summary'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return back()->with('success', 'Machine-readable provision saved with mandatory human-decision control.');
    }

    public function storeSource(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:60', 'regex:/^[A-Z0-9_-]+$/', 'unique:external_hr_sources,code'],
            'name' => ['required', 'string', 'max:160'],
            'source_type' => ['required', Rule::in(['api', 'csv', 'xlsx', 'database', 'sftp'])],
            'base_url' => ['nullable', 'url', 'max:500'],
            'authority' => ['nullable', 'string', 'max:180'],
            'is_authoritative' => ['nullable', 'boolean'],
        ]);

        DB::table('external_hr_sources')->insert([
            'code' => $data['code'],
            'name' => $data['name'],
            'source_type' => $data['source_type'],
            'base_url' => $data['base_url'] ?? null,
            'authority' => $data['authority'] ?? null,
            'is_authoritative' => (bool) ($data['is_authoritative'] ?? false),
            'is_active' => true,
            'configuration' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'External HR source registered. Credentials are intentionally not accepted in this form.');
    }

    public function importHrmis(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'external_hr_source_id' => ['required', 'integer', 'exists:external_hr_sources,id'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $source = DB::table('external_hr_sources')
            ->where('id', $data['external_hr_source_id'])
            ->where('is_active', true)
            ->first();

        if (! $source) {
            throw ValidationException::withMessages([
                'external_hr_source_id' => 'The selected HR source is inactive or unavailable.',
            ]);
        }

        $runId = DB::table('external_hr_sync_runs')->insertGetId([
            'external_hr_source_id' => $source->id,
            'direction' => 'inbound',
            'status' => 'running',
            'started_by' => $request->user()->id,
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $handle = fopen($data['file']->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded CSV could not be opened.']);
        }

        $headers = fgetcsv($handle);
        $requiredHeaders = ['pay_no', 'name'];
        if (! is_array($headers) || array_diff($requiredHeaders, $headers) !== []) {
            fclose($handle);
            DB::table('external_hr_sync_runs')->where('id', $runId)->update([
                'status' => 'failed',
                'error_message' => 'CSV must contain pay_no and name columns.',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'file' => 'CSV must contain at least pay_no and name columns.',
            ]);
        }

        $seen = 0;
        $matched = 0;
        $changed = 0;
        $conflicted = 0;
        $allowedFields = [
            'name',
            'nic_number',
            'date_of_birth',
            'date_of_appointment',
            'date_current_grade',
            'next_increment_date',
            'professional_registration_no',
            'professional_registration_expiry',
        ];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($headers)) {
                    $conflicted++;

                    continue;
                }

                $seen++;
                $record = array_combine($headers, $row);
                $payNo = trim((string) ($record['pay_no'] ?? ''));

                if ($payNo === '') {
                    $conflicted++;

                    continue;
                }

                $employee = Employee::query()->wherePiiEquals('pay_no', $payNo)->first();
                if (! $employee) {
                    ExternalHrReconciliationItem::create([
                        'sync_run_id' => $runId,
                        'employee_id' => null,
                        'external_identifier' => $payNo,
                        'field_name' => 'employee_match',
                        'local_value' => null,
                        'external_value' => (string) ($record['name'] ?? ''),
                        'status' => 'review',
                        'authority_preference' => $source->is_authoritative ? 'external' : 'manual',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $conflicted++;

                    continue;
                }

                $matched++;
                foreach ($allowedFields as $field) {
                    if (! array_key_exists($field, $record)) {
                        continue;
                    }

                    $external = trim((string) $record[$field]);
                    $local = $employee->getAttribute($field);
                    $localText = $local instanceof \DateTimeInterface
                        ? $local->format('Y-m-d')
                        : trim((string) ($local ?? ''));

                    if ($external === $localText) {
                        continue;
                    }

                    ExternalHrReconciliationItem::create([
                        'sync_run_id' => $runId,
                        'employee_id' => $employee->id,
                        'external_identifier' => $payNo,
                        'field_name' => $field,
                        'local_value' => $localText,
                        'external_value' => $external,
                        'status' => 'review',
                        'authority_preference' => $source->is_authoritative ? 'external' : 'manual',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $changed++;
                }
            }

            fclose($handle);
            DB::table('external_hr_sync_runs')->where('id', $runId)->update([
                'status' => 'completed',
                'records_seen' => $seen,
                'records_matched' => $matched,
                'records_changed' => $changed,
                'records_conflicted' => $conflicted,
                'summary' => json_encode([
                    'mode' => 'reconciliation_only',
                    'automatic_overwrite' => false,
                ], JSON_THROW_ON_ERROR),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
        } catch (\Throwable $exception) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            DB::rollBack();
            DB::table('external_hr_sync_runs')->where('id', $runId)->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 5000),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
            throw $exception;
        }

        return back()->with(
            'success',
            "HRMIS reconciliation completed: {$seen} rows, {$matched} matched employees, {$changed} field differences. No employee values were overwritten automatically."
        );
    }

    public function resolveReconciliation(Request $request, int $item): RedirectResponse
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'resolution' => ['required', Rule::in(['accept_external', 'keep_local', 'defer'])],
            'resolution_note' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        $row = ExternalHrReconciliationItem::query()->whereKey($item)->lockForUpdate()->first();
        abort_unless($row, 404);

        DB::transaction(function () use ($row, $data, $request): void {
            if ($data['resolution'] === 'accept_external') {
                if (! $row->employee_id || ! Schema::hasColumn('employees', $row->field_name)) {
                    throw ValidationException::withMessages([
                        'resolution' => 'This reconciliation item cannot be applied directly to an employee field.',
                    ]);
                }

                $employee = Employee::query()->findOrFail((int) $row->employee_id);
                $employee->update([
                    $row->field_name => $row->external_value !== '' ? $row->external_value : null,
                    'updated_by' => $request->user()->id,
                ]);

                EmployeeFieldProvenance::create([
                    'employee_id' => $row->employee_id,
                    'field_name' => $row->field_name,
                    'field_value' => $row->external_value,
                    'source_type' => 'ministry_hrmis',
                    'source_reference' => 'HRMIS reconciliation item #'.$row->id,
                    'source_system' => 'National HRMIS',
                    'is_authoritative' => true,
                    'confidence' => 100,
                    'recorded_by' => $request->user()->id,
                    'recorded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('external_hr_reconciliation_items')->where('id', $row->id)->update([
                'status' => $data['resolution'] === 'defer' ? 'deferred' : 'resolved',
                'resolution_note' => $data['resolution_note'],
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'HRMIS reconciliation item resolved with provenance retained.');
    }

    public function downloadCaseBundle(Request $request, int $bundle): BinaryFileResponse
    {
        $this->authorizeAccess($request);
        $row = DB::table('formal_case_bundles')->where('id', $bundle)->first();
        abort_unless($row, 404);
        abort_unless($row->file_path && Storage::disk('local')->exists($row->file_path), 404);

        return response()->download(
            Storage::disk('local')->path($row->file_path),
            $row->bundle_no.'.json',
            ['Content-Type' => 'application/json']
        );
    }

    public function downloadAssuranceExport(Request $request, int $export): BinaryFileResponse
    {
        $this->authorizeAccess($request);
        $row = DB::table('assurance_exports')->where('id', $export)->first();
        abort_unless($row, 404);
        abort_unless($row->file_path && Storage::disk('local')->exists($row->file_path), 404);

        return response()->download(
            Storage::disk('local')->path($row->file_path),
            $row->export_no.'.json',
            ['Content-Type' => 'application/json']
        );
    }

    public function storeDocumentRequirement(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'lifecycle_event' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_-]+$/'],
            'document_category' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_-]+$/'],
            'label' => ['required', 'string', 'max:180'],
            'applicable_position_code' => ['nullable', 'string', 'max:30'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_mandatory' => ['nullable', 'boolean'],
        ]);

        DB::table('document_requirements')->insert($data + [
            'is_mandatory' => (bool) ($data['is_mandatory'] ?? false),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Lifecycle document requirement saved.');
    }

    public function storeProvenance(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'field_name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'field_value' => ['nullable', 'string', 'max:5000'],
            'source_type' => ['required', Rule::in(['appointment_letter', 'service_file', 'ministry_hrmis', 'circular', 'service_minute', 'verified_document', 'administrative_decision', 'manual_verified', 'other'])],
            'source_reference' => ['nullable', 'string', 'max:180'],
            'source_system' => ['nullable', 'string', 'max:100'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_authoritative' => ['nullable', 'boolean'],
            'confidence' => ['required', 'integer', 'between:0,100'],
        ]);

        EmployeeFieldProvenance::create($data + [
            'is_authoritative' => (bool) ($data['is_authoritative'] ?? false),
            'recorded_by' => $request->user()->id,
            'recorded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Field-level provenance recorded.');
    }

    public function storeTemporalEvent(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'entity_type' => ['required', Rule::in(['employee', 'position', 'unit', 'approved_cadre', 'allocation'])],
            'entity_id' => ['required', 'integer', 'min:1'],
            'event_type' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_-]+$/'],
            'effective_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'before_state' => ['nullable', 'json'],
            'after_state' => ['required', 'json'],
            'source_type' => ['nullable', 'string', 'max:80'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_reference' => ['nullable', 'string', 'max:180'],
        ]);

        DB::table('establishment_temporal_events')->insert($data + [
            'recorded_by' => $request->user()->id,
            'recorded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Effective-dated temporal event recorded.');
    }

    public function generateCaseBundle(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'case_type' => ['required', Rule::in(['promotion', 'transfer', 'correction', 'retirement', 'cadre'])],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'source_type' => ['nullable', 'string', 'max:80'],
            'source_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($data['case_type'] !== 'cadre' && empty($data['employee_id'])) {
            throw ValidationException::withMessages([
                'employee_id' => 'An employee is required for this case type.',
            ]);
        }

        $employee = ! empty($data['employee_id']) ? Employee::find($data['employee_id']) : null;
        $manifest = [
            'generated_at' => now()->toIso8601String(),
            'case_type' => $data['case_type'],
            'employee' => $employee?->only(['id', 'name', 'pay_no', 'position_id', 'unit_id', 'subject_code_id']),
            'documents' => $employee && Schema::hasTable('employee_documents')
                ? DB::table('employee_documents')->where('employee_id', $employee->id)->get()->toArray()
                : [],
            'provenance' => $employee && Schema::hasTable('employee_field_provenance')
                ? EmployeeFieldProvenance::query()->where('employee_id', $employee->id)->get()->toArray()
                : [],
            'eligibility_findings' => $employee && Schema::hasTable('administrative_eligibility_findings')
                ? DB::table('administrative_eligibility_findings')->where('employee_id', $employee->id)->get()->toArray()
                : [],
            'source' => [
                'type' => $data['source_type'] ?? null,
                'id' => $data['source_id'] ?? null,
            ],
        ];

        $json = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $hash = hash('sha256', $json);
        $number = 'CASE-'.now()->format('Ymd-His').'-'.strtoupper(substr($hash, 0, 8));

        $path = 'case-bundles/'.$number.'.json';
        Storage::disk('local')->put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        DB::table('formal_case_bundles')->insert([
            'bundle_no' => $number,
            'case_type' => $data['case_type'],
            'employee_id' => $data['employee_id'] ?? null,
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'status' => 'generated',
            'manifest' => $json,
            'content_hash' => $hash,
            'file_path' => $path,
            'generated_by' => $request->user()->id,
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', "Formal evidence bundle {$number} generated with SHA-256 integrity hash.");
    }

    public function generateAssuranceExport(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'audience' => ['required', Rule::in(['management_services', 'ministry', 'audit', 'institution'])],
            'report_type' => ['required', Rule::in(['establishment', 'workforce', 'vacancy', 'retirement', 'governance', 'data_quality'])],
            'as_at_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $asAt = Carbon::parse($data['as_at_date']);
        $manifest = [
            'audience' => $data['audience'],
            'report_type' => $data['report_type'],
            'as_at_date' => $asAt->toDateString(),
            'temporal_snapshot' => $this->service->temporalSnapshot($asAt),
            'generated_from' => 'Carder Management authoritative establishment record',
        ];
        $json = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $hash = hash('sha256', $json);
        $number = 'EXP-'.now()->format('Ymd-His').'-'.strtoupper(substr($hash, 0, 8));

        $path = 'assurance-exports/'.$number.'.json';
        Storage::disk('local')->put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        DB::table('assurance_exports')->insert([
            'export_no' => $number,
            'audience' => $data['audience'],
            'report_type' => $data['report_type'],
            'as_at_date' => $asAt->toDateString(),
            'parameters' => json_encode([], JSON_THROW_ON_ERROR),
            'manifest' => $json,
            'content_hash' => $hash,
            'file_path' => $path,
            'generated_by' => $request->user()->id,
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', "Assurance export {$number} generated from the temporal establishment record.");
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole(['super_admin', 'planning_officer', 'admin_group']),
            403
        );
    }

    private function countTable(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }

    private function missingDocumentCount(): int
    {
        if (! Schema::hasTable('document_requirements') || ! Schema::hasTable('employee_documents')) {
            return 0;
        }

        return DB::table('document_requirements')->where('is_active', true)->where('is_mandatory', true)->count();
    }
}
