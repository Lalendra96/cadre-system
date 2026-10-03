<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DataQualityIssue;
use App\Models\DataQualityRule;
use App\Models\Employee;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DataQualityController extends Controller
{
    public function index(Request $request)
    {
        $employees = WorkforceScopeService::employeeQuery($request->user())
            ->with(['position:id,title', 'subjectCode:id,code', 'unit:id,name'])
            ->where('is_active', true)
            ->get();
        $issues = $this->detectIssues($employees);
        $this->syncIssues($issues, $request);
        $persistent = DataQualityIssue::with(['employee.position', 'employee.unit', 'employee.subjectCode', 'assignee'])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereNotIn('status', ['verified'])
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->latest()
            ->get();
        $totalChecks = max($employees->count() * 12, 1);
        $score = max(0, round((1 - $issues->count() / $totalChecks) * 100));
        $bySeverity = $persistent->countBy('severity');
        $byType = $persistent->groupBy('label')->map->count()->sortDesc();
        $isScoped =
            $request->user()->isSubjectOfficer() &&
            ! $request->user()->isSuperAdmin() &&
            ! $request->user()->isPlanningOfficer();

        return view(
            'data-quality.index',
            compact('employees', 'persistent', 'score', 'bySeverity', 'byType', 'isScoped'),
        );
    }

    public function detectIssues(Collection $employees): Collection
    {
        $enabled = DataQualityRule::where('is_enabled', true)->pluck('severity', 'code');
        $useDb = $enabled->isNotEmpty();
        $issues = collect();
        foreach ($employees as $e) {
            $checks = [
                ['missing_nic', 'Missing NIC', empty($e->nic_number), 'high'],
                ['missing_dob', 'Missing date of birth', empty($e->date_of_birth), 'high'],
                ['missing_email', 'Missing email address', empty($e->email), 'low'],
                ['missing_mobile', 'Missing mobile number', empty($e->whatsapp_mobile), 'low'],
                ['missing_position', 'Missing position', empty($e->position_id), 'critical'],
                ['missing_unit', 'Missing unit', empty($e->unit_id), 'critical'],
                ['missing_subject_code', 'Missing subject code', empty($e->subject_code_id), 'critical'],
                ['missing_appointment', 'Missing appointment date', empty($e->date_of_appointment), 'medium'],
                ['missing_increment', 'No increment history', ! $e->incrementRecords()->active()->exists(), 'medium'],
                [
                    'missing_emergency_contact',
                    'Missing emergency contact',
                    empty($e->emergency_contact_name) || empty($e->emergency_contact_mobile),
                    'low',
                ],
                [
                    'missing_next_increment',
                    'Missing next increment date',
                    empty($e->next_increment_date) &&
                    ! $e
                        ->incrementRecords()
                        ->active()
                        ->whereDate('increment_date', '>=', now()->toDateString())
                        ->exists(),
                    'medium',
                ],
                [
                    'retirement_overdue',
                    'Past retirement date but still active',
                    $e->retire_date && $e->retire_date->isPast(),
                    'critical',
                ],
                [
                    'future_appointment',
                    'Appointment date is in the future',
                    $e->date_of_appointment && $e->date_of_appointment->isFuture(),
                    'high',
                ],
                [
                    'future_joined_service',
                    'Public-service joining date is in the future',
                    $e->date_joined_public_service && $e->date_joined_public_service->isFuture(),
                    'high',
                ],
                ['inactive_unit', 'Employee is assigned to an inactive unit', $e->unit && ! $e->unit->is_active, 'high'],
                [
                    'missing_registration_expiry',
                    'Professional registration has no expiry date',
                    ! empty($e->professional_registration_no) && empty($e->professional_registration_expiry),
                    'medium',
                ],
                [
                    'acting_without_end_date',
                    'Acting appointment has no end date',
                    $e->actingAppointments()->where('is_active', true)->whereNull('end_date')->exists(),
                    'medium',
                ],
            ];
            if ($e->date_of_birth && $e->date_of_appointment) {
                $checks[] = [
                    'invalid_appointment_age',
                    'Appointment date precedes date of birth',
                    $e->date_of_appointment->lt($e->date_of_birth),
                    'critical',
                ];
            }
            foreach ($checks as [$code, $label, $failed, $severity]) {
                if ($failed && (! $useDb || $enabled->has($code))) {
                    $issues->push([
                        'code' => $code,
                        'label' => $label,
                        'severity' => $enabled[$code] ?? $severity,
                        'employee' => $e,
                    ]);
                }
            }
        }
        // Duplicate detection uses deterministic HMAC blind indexes, never plaintext PII.
        $duplicateNicHmacs = Employee::whereIn('id', $employees->pluck('id'))
            ->whereNotNull('nic_number_hmac')
            ->selectRaw('nic_number_hmac, count(*) c')
            ->groupBy('nic_number_hmac')
            ->havingRaw('count(*) > 1')
            ->pluck('nic_number_hmac');
        foreach (Employee::whereIn('id', $employees->pluck('id'))->whereIn('nic_number_hmac', $duplicateNicHmacs)->get() as $e) {
            if (! $useDb || $enabled->has('duplicate_nic')) {
                $issues->push([
                    'code' => 'duplicate_nic',
                    'label' => 'Duplicate NIC',
                    'severity' => $enabled['duplicate_nic'] ?? 'critical',
                    'employee' => $e,
                ]);
            }
        }

        $duplicatePayHmacs = Employee::whereIn('id', $employees->pluck('id'))
            ->whereNotNull('pay_no_hmac')
            ->selectRaw('pay_no_hmac, count(*) c')
            ->groupBy('pay_no_hmac')
            ->havingRaw('count(*) > 1')
            ->pluck('pay_no_hmac');
        foreach (Employee::whereIn('id', $employees->pluck('id'))->whereIn('pay_no_hmac', $duplicatePayHmacs)->get() as $e) {
            if (! $useDb || $enabled->has('duplicate_pay_no')) {
                $issues->push([
                    'code' => 'duplicate_pay_no',
                    'label' => 'Duplicate Pay No.',
                    'severity' => $enabled['duplicate_pay_no'] ?? 'critical',
                    'employee' => $e,
                ]);
            }
        }

        return $issues;
    }

    private function syncIssues(Collection $detected, Request $request): void
    {
        $live = [];
        foreach ($detected as $i) {
            $key = $i['employee']->id.'|'.$i['code'];
            $live[$key] = true;
            DataQualityIssue::updateOrCreate(
                ['employee_id' => $i['employee']->id, 'rule_code' => $i['code']],
                ['label' => $i['label'], 'severity' => $i['severity'], 'status' => 'detected'],
            );
        }
        DataQualityIssue::whereIn('employee_id', $detected->pluck('employee.id')->filter()->unique())
            ->whereIn('status', ['detected', 'assigned', 'in_progress', 'corrected'])
            ->get()
            ->each(function ($issue) use ($live) {
                if (! isset($live[$issue->employee_id.'|'.$issue->rule_code])) {
                    $issue->update(['status' => 'verified', 'verified_at' => now()]);
                }
            });
    }

    public function update(Request $request, DataQualityIssue $dataQualityIssue)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $dataQualityIssue->employee);
        $data = $request->validate([
            'status' => 'required|in:detected,assigned,in_progress,corrected,verified',
            'resolution_note' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date',
        ]);
        if ($data['status'] === 'corrected') {
            $data['resolved_by'] = $request->user()->id;
            $data['resolved_at'] = now();
        }
        if ($data['status'] === 'verified') {
            $data['verified_by'] = $request->user()->id;
            $data['verified_at'] = now();
        }
        $dataQualityIssue->update($data);

        return back()->with('success', 'Data-quality issue updated.');
    }

    public function rules(Request $request)
    {
        $defaults = [
            ['missing_nic', 'Missing NIC', 'high'],
            ['missing_dob', 'Missing date of birth', 'high'],
            ['missing_email', 'Missing email address', 'low'],
            ['missing_mobile', 'Missing mobile number', 'low'],
            ['missing_position', 'Missing position', 'critical'],
            ['missing_unit', 'Missing unit', 'critical'],
            ['missing_subject_code', 'Missing subject code', 'critical'],
            ['missing_appointment', 'Missing appointment date', 'medium'],
            ['missing_increment', 'No increment history', 'medium'],
            ['missing_emergency_contact', 'Missing emergency contact', 'low'],
            ['missing_next_increment', 'Missing next increment date', 'medium'],
            ['retirement_overdue', 'Past retirement date but still active', 'critical'],
            ['invalid_appointment_age', 'Appointment date precedes DOB', 'critical'],
            ['duplicate_nic', 'Duplicate NIC', 'critical'],
            ['duplicate_pay_no', 'Duplicate Pay No.', 'critical'],
            ['future_appointment', 'Appointment date is in the future', 'high'],
            ['future_joined_service', 'Public-service joining date is in the future', 'high'],
            ['inactive_unit', 'Employee is assigned to an inactive unit', 'high'],
            ['missing_registration_expiry', 'Professional registration has no expiry date', 'medium'],
            ['acting_without_end_date', 'Acting appointment has no end date', 'medium'],
        ];
        foreach ($defaults as $n => $r) {
            DataQualityRule::firstOrCreate(
                ['code' => $r[0]],
                ['label' => $r[1], 'severity' => $r[2], 'is_enabled' => true, 'sort_order' => $n * 10],
            );
        }

        return view('data-quality.rules', ['rules' => DataQualityRule::orderBy('sort_order')->get()]);
    }

    public function updateRule(Request $request, DataQualityRule $rule)
    {
        $data = $request->validate([
            'severity' => 'required|in:low,medium,high,critical',
            'is_enabled' => 'nullable|boolean',
        ]);
        $data['is_enabled'] = $request->boolean('is_enabled');
        $rule->update($data);

        return back()->with('success', 'Data-quality rule updated.');
    }
}
