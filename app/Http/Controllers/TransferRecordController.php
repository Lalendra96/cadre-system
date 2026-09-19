<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\CarderMonthlyEntry;
use App\Models\Employee;
use App\Models\TransferRecord;
use App\Models\User;
use App\Services\AdministrativeDecisionService;
use App\Services\AuditLogService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransferRecordController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $user = $request->user();

        $query = TransferRecord::active()->with([
            'employee.position',
            'carderEntry.position',
            'recordedBy',
        ]);

        if (
            $user->isSubjectOfficer()
            && ! $user->isSuperAdmin()
            && ! $user->isPlanningOfficer()
            && ! $user->isHrRecordsManager()
        ) {
            $query->whereIn(
                'employee_id',
                WorkforceScopeService::allocatedEmployeeIds(
                    $user
                )
            );
        }

        if ($direction = $request->query('direction')) {
            $query->direction($direction);
        }

        if ($from = $request->query('from')) {
            $query->where(
                'effective_date',
                '>=',
                $from
            );
        }

        if ($to = $request->query('to')) {
            $query->where(
                'effective_date',
                '<=',
                $to
            );
        }

        $records = $query
            ->orderByDesc('effective_date')
            ->paginate(25)
            ->withQueryString();

        return view(
            'transfer-records.index',
            compact('records')
        );
    }

    public function create(Request $request)
    {
        $entryId = $request->query('entry_id');

        $entry = $entryId
            ? CarderMonthlyEntry::find($entryId)
            : null;

        return view(
            'transfer-records.form',
            [
                'record' => new TransferRecord([
                    'employee_id' => $request->integer(
                        'employee_id'
                    ) ?: null,
                ]),
                'entry' => $entry,
                'employees' => $this->assignableEmployees(
                    $request->user()
                ),
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $data = $this->validatedTransferData(
            $request,
            true
        );

        $employee = Employee::with('position')
            ->findOrFail(
                $data['employee_id']
            );

        abort_unless(
            $request
                ->user()
                ->canManageEmployeeHrRecord(
                    $employee
                ),
            403
        );

        unset($data['review_confirmed']);

        $decision = AdministrativeDecisionService::request(
            type: 'transfer_record',
            employee: $employee,
            payload: $data,
            requester: $request->user(),
            summary: 'Proposed '
                . strtoupper($data['direction'])
                . ' transfer for '
                . $employee->display_name
                . ' effective '
                . $data['effective_date'],
            sourceReference: $data['transfer_board_ref_no']
                ?? $data['psc_circular_no']
                ?? null
        );

        return redirect()
            ->route(
                'administrative-decisions.show',
                $decision
            )
            ->with(
                'success',
                'Transfer submitted for independent human approval. No transfer record has been applied yet.'
            );
    }

    public function edit(
        Request $request,
        TransferRecord $transferRecord
    ) {
        $this->authorizeRecord(
            $request,
            $transferRecord
        );

        return view(
            'transfer-records.form',
            [
                'record' => $transferRecord,
                'entry' => $transferRecord->carderEntry,
                'employees' => $this->assignableEmployees(
                    $request->user()
                ),
            ]
        );
    }

    public function update(
        Request $request,
        TransferRecord $transferRecord
    ): RedirectResponse {
        $this->authorizeRecord(
            $request,
            $transferRecord
        );

        $data = $this->validatedTransferData(
            $request,
            false
        );

        $employee = Employee::with('position')
            ->findOrFail(
                $data['employee_id']
            );

        abort_unless(
            $request
                ->user()
                ->canManageEmployeeHrRecord(
                    $employee
                ),
            403
        );

        unset($data['review_confirmed']);

        $data['employee_name'] = $employee->name;
        $data['designation'] = $employee->position?->title
            ?? $data['designation']
            ?? null;

        $old = $transferRecord->getOriginal();

        $transferRecord->update($data);

        AuditLogService::updated(
            $transferRecord,
            $old,
            'Updated an already approved transfer record. Existing correction history retained in audit log.'
        );

        return redirect()
            ->route('transfer-records.index')
            ->with(
                'success',
                'Approved transfer record corrected. The before/after change is retained in the audit log.'
            );
    }

    public function toggle(
        Request $request,
        TransferRecord $transferRecord
    ): RedirectResponse {
        $this->authorizeRecord(
            $request,
            $transferRecord
        );

        return $this->performToggle(
            request: $request,
            model: $transferRecord,
            label: 'Transfer record for "'
                . $transferRecord->employee_name
                . '"',
            requireReason: true
        );
    }

    private function authorizeRecord(
        Request $request,
        TransferRecord $transferRecord
    ): void {
        abort_unless(
            $request
                ->user()
                ->canManageEmployeeHrRecord(
                    $transferRecord->employee
                ),
            403
        );
    }

    private function assignableEmployees(User $user)
    {
        if (
            $user->isSuperAdmin()
            || $user->isPlanningOfficer()
            || $user->isHrRecordsManager()
        ) {
            return Employee::active()
                ->with([
                    'position:id,title',
                    'unit:id,name',
                ])
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'pay_no',
                    'position_id',
                    'unit_id',
                ]);
        }

        return WorkforceScopeService::employeeQuery(
            $user
        )
            ->active()
            ->with([
                'position:id,title',
                'unit:id,name',
            ])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'pay_no',
                'subject_code_id',
                'position_id',
                'unit_id',
            ]);
    }

    private function validatedTransferData(
        Request $request,
        bool $includeCarderEntry
    ): array {
        $rules = [
            'employee_id' => [
                'required',
                'integer',
                'exists:employees,id',
            ],
            'employee_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'designation' => [
                'nullable',
                'string',
                'max:100',
            ],
            'direction' => [
                'required',
                'in:in,out',
            ],
            'transfer_type' => [
                'required',
                'in:permanent,temporary,deputation,secondment,'
                . 'internal_unit_transfer,internal_institution_transfer,'
                . 'incoming_external_transfer,outgoing_external_transfer,'
                . 'temporary_attachment,permanent_release,reversion,'
                . 'inter_ministry_transfer,inter_department_transfer,'
                . 'provincial_central_movement,other',
            ],
            'from_location' => [
                'nullable',
                'string',
                'max:150',
            ],
            'to_location' => [
                'nullable',
                'string',
                'max:150',
            ],
            'effective_date' => [
                'required',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'psc_circular_no' => [
                'nullable',
                'string',
                'max:100',
            ],
            'transfer_board_ref_no' => [
                'nullable',
                'string',
                'max:100',
            ],
            'transfer_board_decision_date' => [
                'nullable',
                'date',
            ],
            'review_confirmed' => [
                'accepted',
            ],
        ];

        if ($includeCarderEntry) {
            $rules = [
                'carder_entry_id' => [
                    'nullable',
                    'integer',
                    'exists:carder_monthly_entries,id',
                ],
            ] + $rules;
        }

        return $request->validate($rules);
    }
}
