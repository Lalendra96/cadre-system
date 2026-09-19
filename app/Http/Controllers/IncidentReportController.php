<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\IncidentEvent;
use App\Models\IncidentReport;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IncidentReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = IncidentReport::query()
            ->with([
                'reporter',
                'assignee',
                'affectedEmployee',
            ])
            ->latest('reported_at');

        if (! $this->canManageIncidents($user)) {
            $query->where(
                'reported_by',
                $user->id
            );
        }

        if ($status = $request->query('status')) {
            $query->where(
                'status',
                $status
            );
        }

        if ($severity = $request->query('severity')) {
            $query->where(
                'severity',
                $severity
            );
        }

        if ($category = $request->query('category')) {
            $query->where(
                'category',
                $category
            );
        }

        $incidents = $query
            ->paginate(25)
            ->withQueryString();

        $scope = IncidentReport::query();

        if (! $this->canManageIncidents($user)) {
            $scope->where(
                'reported_by',
                $user->id
            );
        }

        $kpis = [
            'open' => (clone $scope)
                ->whereNotIn(
                    'status',
                    ['closed']
                )
                ->count(),
            'critical' => (clone $scope)
                ->where('severity', 'critical')
                ->whereNotIn(
                    'status',
                    ['closed']
                )
                ->count(),
            'investigating' => (clone $scope)
                ->whereIn(
                    'status',
                    [
                        'triaged',
                        'under_investigation',
                        'corrective_action',
                        'reopened',
                    ]
                )
                ->count(),
            'resolved' => (clone $scope)
                ->where('status', 'resolved')
                ->count(),
        ];

        return view(
            'incidents.index',
            compact(
                'incidents',
                'kpis'
            )
        );
    }

    public function create(Request $request)
    {
        $employees = WorkforceScopeService::employeeQuery(
            $request->user()
        )
            ->active()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'pay_no',
            ]);

        return view(
            'incidents.create',
            compact('employees')
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $data = $request->validate([
            'title' => [
                'required',
                'string',
                'max:200',
            ],
            'category' => [
                'required',
                Rule::in(
                    array_keys(
                        IncidentReport::CATEGORIES
                    )
                ),
            ],
            'severity' => [
                'required',
                Rule::in(
                    array_keys(
                        IncidentReport::SEVERITIES
                    )
                ),
            ],
            'description' => [
                'required',
                'string',
                'min:20',
                'max:5000',
            ],
            'detected_at' => [
                'nullable',
                'date',
                'before_or_equal:now',
            ],
            'affected_records_count' => [
                'nullable',
                'integer',
                'min:0',
                'max:10000000',
            ],
            'affected_employee_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
            ],
            'related_reference' => [
                'nullable',
                'string',
                'max:180',
            ],
            'impact_summary' => [
                'nullable',
                'string',
                'max:3000',
            ],
            'immediate_action' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        if (! empty($data['affected_employee_id'])) {
            $employee = Employee::findOrFail(
                $data['affected_employee_id']
            );

            WorkforceScopeService::authorizeEmployee(
                $request->user(),
                $employee
            );
        }

        $incident = DB::transaction(
            function () use (
                $request,
                $data
            ): IncidentReport {
                $incident = IncidentReport::create(
                    $data + [
                        'status' => 'open',
                        'reported_by' => $request->user()->id,
                        'reported_at' => now(),
                        'is_active' => true,
                    ]
                );

                $incident->update([
                    'reference_no' => sprintf(
                        'INC-%s-%06d',
                        now()->format('Y'),
                        $incident->id
                    ),
                ]);

                $this->event(
                    $incident,
                    'reported',
                    $request->user(),
                    null,
                    'open',
                    'Incident/error reported for triage.',
                    [
                        'category' => $incident->category,
                        'severity' => $incident->severity,
                    ]
                );

                AuditLogService::created(
                    $incident,
                    'Incident/error report created: '
                        . $incident->reference_no
                );

                return $incident;
            }
        );

        $this->notifyManagers(
            $incident,
            'incident_reported',
            'New incident requires triage',
            $incident->reference_no
                . ' · '
                . $incident->severity_label
                . ' · '
                . $incident->category_label
        );

        return redirect()
            ->route(
                'incidents.show',
                $incident
            )
            ->with(
                'success',
                'Incident recorded as '
                    . $incident->reference_no
                    . '. It is now awaiting triage.'
            );
    }

    public function show(
        Request $request,
        IncidentReport $incidentReport
    ) {
        $this->authorizeView(
            $request,
            $incidentReport
        );

        $incidentReport->load([
            'reporter',
            'assignee',
            'resolvedBy',
            'closedBy',
            'affectedEmployee',
            'events.actor',
        ]);

        $managers = $this->managerUsers();

        return view(
            'incidents.show',
            [
                'incident' => $incidentReport,
                'canManage' => $this->canManageIncidents(
                    $request->user()
                ),
                'managers' => $managers,
            ]
        );
    }

    public function triage(
        Request $request,
        IncidentReport $incidentReport
    ): RedirectResponse {
        $this->authorizeManage($request);

        $data = $request->validate([
            'category' => [
                'required',
                Rule::in(
                    array_keys(
                        IncidentReport::CATEGORIES
                    )
                ),
            ],
            'severity' => [
                'required',
                Rule::in(
                    array_keys(
                        IncidentReport::SEVERITIES
                    )
                ),
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'target_resolution_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'impact_summary' => [
                'nullable',
                'string',
                'max:3000',
            ],
            'immediate_action' => [
                'nullable',
                'string',
                'max:3000',
            ],
            'triage_note' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);

        if (! empty($data['assigned_to'])) {
            $assignee = User::findOrFail(
                $data['assigned_to']
            );

            abort_unless(
                $this->canManageIncidents($assignee),
                422,
                'The selected assignee must be an authorised incident manager.'
            );
        }

        $old = $incidentReport->getOriginal();
        $fromStatus = $incidentReport->status;

        $incidentReport->update([
            'category' => $data['category'],
            'severity' => $data['severity'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'target_resolution_date'
                => $data['target_resolution_date'] ?? null,
            'impact_summary' => $data['impact_summary'] ?? null,
            'immediate_action' => $data['immediate_action'] ?? null,
            'status' => 'triaged',
        ]);

        $this->event(
            $incidentReport,
            'triaged',
            $request->user(),
            $fromStatus,
            'triaged',
            $data['triage_note'],
            [
                'assigned_to' => $data['assigned_to'] ?? null,
                'target_resolution_date'
                    => $data['target_resolution_date'] ?? null,
            ]
        );

        AuditLogService::updated(
            $incidentReport,
            $old,
            'Incident triaged: '
                . $incidentReport->reference_no
        );

        $this->notifyReporter(
            $incidentReport,
            'incident_triaged',
            'Incident triaged',
            $incidentReport->reference_no
                . ' is now under managed review.'
        );

        return back()->with(
            'success',
            'Incident triage recorded.'
        );
    }

    public function recordInvestigation(
        Request $request,
        IncidentReport $incidentReport
    ): RedirectResponse {
        $this->authorizeManage($request);

        $data = $request->validate([
            'root_cause' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'corrective_action' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'preventive_action' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'investigation_note' => [
                'required',
                'string',
                'min:10',
                'max:3000',
            ],
        ]);

        $old = $incidentReport->getOriginal();
        $fromStatus = $incidentReport->status;

        $incidentReport->update([
            'root_cause' => $data['root_cause'],
            'corrective_action' => $data['corrective_action'],
            'preventive_action'
                => $data['preventive_action'] ?? null,
            'status' => 'corrective_action',
        ]);

        $this->event(
            $incidentReport,
            'investigation_recorded',
            $request->user(),
            $fromStatus,
            'corrective_action',
            $data['investigation_note'],
            [
                'root_cause_recorded' => true,
                'corrective_action_recorded' => true,
                'preventive_action_recorded'
                    => ! empty($data['preventive_action']),
            ]
        );

        AuditLogService::updated(
            $incidentReport,
            $old,
            'Incident investigation/corrective action recorded: '
                . $incidentReport->reference_no
        );

        return back()->with(
            'success',
            'Investigation and corrective-action plan recorded.'
        );
    }

    public function recordCorrection(
        Request $request,
        IncidentReport $incidentReport
    ): RedirectResponse {
        $this->authorizeManage($request);

        $data = $request->validate([
            'correction_reference' => [
                'required',
                'string',
                'max:180',
            ],
            'correction_type' => [
                'required',
                Rule::in([
                    'data_correction',
                    'configuration_change',
                    'code_fix',
                    'report_regeneration',
                    'access_correction',
                    'process_correction',
                    'other',
                ]),
            ],
            'before_summary' => [
                'required',
                'string',
                'min:5',
                'max:3000',
            ],
            'after_summary' => [
                'required',
                'string',
                'min:5',
                'max:3000',
            ],
            'verification_note' => [
                'required',
                'string',
                'min:10',
                'max:3000',
            ],
        ]);

        $this->event(
            $incidentReport,
            'correction_recorded',
            $request->user(),
            $incidentReport->status,
            $incidentReport->status,
            $data['verification_note'],
            [
                'correction_reference'
                    => $data['correction_reference'],
                'correction_type'
                    => $data['correction_type'],
                'before_summary'
                    => $data['before_summary'],
                'after_summary'
                    => $data['after_summary'],
            ]
        );

        return back()->with(
            'success',
            'Correction added to the immutable incident history.'
        );
    }

    public function resolve(
        Request $request,
        IncidentReport $incidentReport
    ): RedirectResponse {
        $this->authorizeManage($request);

        $data = $request->validate([
            'resolution_note' => [
                'required',
                'string',
                'min:20',
                'max:3000',
            ],
            'confirm_correction_verified' => [
                'accepted',
            ],
        ]);

        abort_unless(
            $incidentReport
                ->events()
                ->where(
                    'event_type',
                    'correction_recorded'
                )
                ->exists(),
            422,
            'Record at least one correction/verification entry before resolving the incident.'
        );

        $old = $incidentReport->getOriginal();
        $fromStatus = $incidentReport->status;

        $incidentReport->update([
            'status' => 'resolved',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $this->event(
            $incidentReport,
            'resolved',
            $request->user(),
            $fromStatus,
            'resolved',
            $data['resolution_note'],
            [
                'correction_verified' => true,
            ]
        );

        AuditLogService::updated(
            $incidentReport,
            $old,
            'Incident resolved after correction verification: '
                . $incidentReport->reference_no
        );

        $this->notifyReporter(
            $incidentReport,
            'incident_resolved',
            'Incident marked resolved',
            $incidentReport->reference_no
                . ' has been corrected and is awaiting closure.'
        );

        return back()->with(
            'success',
            'Incident marked resolved and retained for closure review.'
        );
    }

    public function close(
        Request $request,
        IncidentReport $incidentReport
    ): RedirectResponse {
        $this->authorizeManage($request);

        abort_unless(
            $incidentReport->status === 'resolved',
            422,
            'Only a resolved incident can be closed.'
        );

        if (
            in_array(
                $incidentReport->severity,
                [
                    'high',
                    'critical',
                ],
                true
            )
            && $incidentReport->resolved_by === $request->user()->id
        ) {
            abort(
                422,
                'High and critical incidents require independent closure by a different authorised manager.'
            );
        }

        $data = $request->validate([
            'closure_notes' => [
                'required',
                'string',
                'min:20',
                'max:3000',
            ],
            'confirm_preventive_review' => [
                'accepted',
            ],
        ]);

        $old = $incidentReport->getOriginal();

        $incidentReport->update([
            'status' => 'closed',
            'closed_by' => $request->user()->id,
            'closed_at' => now(),
            'closure_notes' => $data['closure_notes'],
        ]);

        $this->event(
            $incidentReport,
            'closed',
            $request->user(),
            'resolved',
            'closed',
            $data['closure_notes'],
            [
                'preventive_action_reviewed' => true,
            ]
        );

        AuditLogService::updated(
            $incidentReport,
            $old,
            'Incident closed after governance review: '
                . $incidentReport->reference_no
        );

        $this->notifyReporter(
            $incidentReport,
            'incident_closed',
            'Incident closed',
            $incidentReport->reference_no
                . ' has completed the incident/correction workflow.'
        );

        return back()->with(
            'success',
            'Incident closed with closure history retained.'
        );
    }

    public function reopen(
        Request $request,
        IncidentReport $incidentReport
    ): RedirectResponse {
        $this->authorizeManage($request);

        $data = $request->validate([
            'reopen_reason' => [
                'required',
                'string',
                'min:15',
                'max:3000',
            ],
        ]);

        $fromStatus = $incidentReport->status;
        $old = $incidentReport->getOriginal();

        $incidentReport->update([
            'status' => 'reopened',
            'resolved_by' => null,
            'resolved_at' => null,
            'closed_by' => null,
            'closed_at' => null,
            'closure_notes' => null,
        ]);

        $this->event(
            $incidentReport,
            'reopened',
            $request->user(),
            $fromStatus,
            'reopened',
            $data['reopen_reason']
        );

        AuditLogService::updated(
            $incidentReport,
            $old,
            'Incident reopened: '
                . $incidentReport->reference_no
        );

        $this->notifyReporter(
            $incidentReport,
            'incident_reopened',
            'Incident reopened',
            $incidentReport->reference_no
                . ' requires additional investigation.'
        );

        return back()->with(
            'success',
            'Incident reopened for additional investigation.'
        );
    }

    private function authorizeView(
        Request $request,
        IncidentReport $incident
    ): void {
        $user = $request->user();

        abort_unless(
            $this->canManageIncidents($user)
                || $incident->reported_by === $user->id,
            403
        );
    }

    private function authorizeManage(
        Request $request
    ): void {
        abort_unless(
            $this->canManageIncidents(
                $request->user()
            ),
            403,
            'Only authorised governance/administrative managers may manage incident investigations and corrections.'
        );
    }

    private function canManageIncidents(
        User $user
    ): bool {
        return $user->is_active
            && (
                $user->isSuperAdmin()
                || $user->isPlanningOfficer()
                || $user->isAdministrativeOfficer()
            );
    }

    private function managerUsers()
    {
        return User::active()
            ->with('category')
            ->get()
            ->filter(
                fn (User $user) => $this->canManageIncidents(
                    $user
                )
            )
            ->values();
    }

    private function event(
        IncidentReport $incident,
        string $eventType,
        ?User $actor,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $comment = null,
        array $metadata = []
    ): IncidentEvent {
        return IncidentEvent::create([
            'incident_report_id' => $incident->id,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'metadata' => $metadata ?: null,
            'actor_id' => $actor?->id,
            'created_at' => now(),
        ]);
    }

    private function notifyManagers(
        IncidentReport $incident,
        string $type,
        string $title,
        string $body
    ): void {
        NotificationService::sendToMany(
            $this->managerUsers(),
            type: $type,
            title: $title,
            body: $body,
            link: route(
                'incidents.show',
                $incident
            )
        );
    }

    private function notifyReporter(
        IncidentReport $incident,
        string $type,
        string $title,
        string $body
    ): void {
        NotificationService::send(
            $incident->reporter,
            type: $type,
            title: $title,
            body: $body,
            link: route(
                'incidents.show',
                $incident
            )
        );
    }
}
