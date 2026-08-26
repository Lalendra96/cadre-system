<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\CarderMonthlyEntry;
use App\Models\Employee;
use App\Models\TransferRecord;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Transfer records, including Transfer Board / PSC circular references.
 *
 * DATA SECURITY — this controller previously had NO per-record
 * authorization at all (relied entirely on route middleware), meaning
 * any Subject Officer could create or edit a transfer record naming an
 * employee from a completely different subject code. Fixed to use
 * User::canManageEmployeeHrRecord() — the same single source of truth
 * used by EmployeeInterdictionController and EmployeeLeaveRecordController
 * — everywhere a specific employee is involved.
 *
 * employee_id is nullable on this table (a transfer can reference someone
 * not yet in the system as a free-text name) — canManageEmployeeHrRecord()
 * is null-safe for exactly this case: with no employee to scope against,
 * a Subject Officer is allowed through, matching the table's original
 * lenient design intent.
 */
class TransferRecordController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = TransferRecord::active()
            ->with(['employee', 'carderEntry.position', 'recordedBy']);

        // Subject Officers see records for their own subject codes, plus
        // any record with no linked Employee (nothing to scope against).
        // Elevated roles and HR-records managers see everything.
        if ($user->isSubjectOfficer() && ! $user->isSuperAdmin() && ! $user->isPlanningOfficer() && ! $user->isHrRecordsManager()) {
            $codeIds = $user->effectiveSubjectCodeIds();
            $query->where(function ($q) use ($codeIds) {
                $q->whereNull('employee_id')
                  ->orWhereHas('employee', fn ($eq) => $eq->whereIn('subject_code_id', $codeIds));
            });
        }

        if ($dir = $request->query('direction')) {
            $query->direction($dir);
        }
        if ($from = $request->query('from')) {
            $query->where('effective_date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('effective_date', '<=', $to);
        }

        $records = $query->orderByDesc('effective_date')->paginate(25)->withQueryString();

        return view('transfer-records.index', compact('records'));
    }

    public function create(Request $request)
    {
        $entryId   = $request->query('entry_id');
        $entry     = $entryId ? CarderMonthlyEntry::find($entryId) : null;
        $employees = $this->assignableEmployees($request->user());

        return view('transfer-records.form', [
            'record'    => new TransferRecord(),
            'entry'     => $entry,
            'employees' => $employees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'carder_entry_id' => ['nullable', 'integer', 'exists:carder_monthly_entries,id'],
            'employee_id'     => ['nullable', 'integer', 'exists:employees,id'],
            'employee_name'   => ['required', 'string', 'max:150'],
            'designation'     => ['nullable', 'string', 'max:100'],
            'direction'       => ['required', 'in:in,out'],
            'transfer_type'   => ['required', 'in:permanent,temporary,deputation,secondment'],
            'from_location'   => ['nullable', 'string', 'max:150'],
            'to_location'     => ['nullable', 'string', 'max:150'],
            'effective_date'  => ['required', 'date'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'psc_circular_no'              => ['nullable', 'string', 'max:100'],
            'transfer_board_ref_no'        => ['nullable', 'string', 'max:100'],
            'transfer_board_decision_date' => ['nullable', 'date'],
        ]);

        $employee = ! empty($data['employee_id']) ? Employee::find($data['employee_id']) : null;
        abort_unless(
            $request->user()->canManageEmployeeHrRecord($employee),
            403,
            'You may only record transfers for employees under your assigned subject codes, '
            . 'or if your account holds an authorised Admin Group category.'
        );

        $data['recorded_by'] = $request->user()->id;
        $data['is_active']   = true;

        $record = TransferRecord::create($data);
        AuditLogService::created($record, "Transfer {$record->direction}: {$record->employee_name}");

        if ($record->carder_entry_id) {
            return redirect()->route('carder-entries.edit', $record->carder_entry_id)
                ->with('success', 'Transfer record added.');
        }

        return redirect()->route('transfer-records.index')
            ->with('success', "Transfer record for {$record->employee_name} saved.");
    }

    public function edit(Request $request, TransferRecord $transferRecord)
    {
        $this->authorizeRecord($request, $transferRecord);

        $employees = $this->assignableEmployees($request->user());

        return view('transfer-records.form', [
            'record'    => $transferRecord,
            'entry'     => $transferRecord->carderEntry,
            'employees' => $employees,
        ]);
    }

    public function update(Request $request, TransferRecord $transferRecord): RedirectResponse
    {
        $this->authorizeRecord($request, $transferRecord);

        $data = $request->validate([
            'employee_name'  => ['required', 'string', 'max:150'],
            'designation'    => ['nullable', 'string', 'max:100'],
            'direction'      => ['required', 'in:in,out'],
            'transfer_type'  => ['required', 'in:permanent,temporary,deputation,secondment'],
            'from_location'  => ['nullable', 'string', 'max:150'],
            'to_location'    => ['nullable', 'string', 'max:150'],
            'effective_date' => ['required', 'date'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'psc_circular_no'              => ['nullable', 'string', 'max:100'],
            'transfer_board_ref_no'        => ['nullable', 'string', 'max:100'],
            'transfer_board_decision_date' => ['nullable', 'date'],
        ]);

        $old = $transferRecord->getOriginal();
        $transferRecord->update($data);
        AuditLogService::updated($transferRecord, $old, "Updated transfer: {$transferRecord->employee_name}");

        return redirect()->route('transfer-records.index')
            ->with('success', "Transfer record for {$transferRecord->employee_name} updated.");
    }

    public function toggle(Request $request, TransferRecord $transferRecord): RedirectResponse
    {
        $this->authorizeRecord($request, $transferRecord);

        return $this->performToggle(
            request:       $request,
            model:         $transferRecord,
            label:         "Transfer record for \"{$transferRecord->employee_name}\"",
            requireReason: false,
        );
    }

    private function authorizeRecord(Request $request, TransferRecord $transferRecord): void
    {
        abort_unless(
            $request->user()->canManageEmployeeHrRecord($transferRecord->employee),
            403,
            'You may only manage transfer records for employees under your assigned subject codes, '
            . 'or if your account holds an authorised Admin Group category.'
        );
    }

    /**
     * Employees the officer may pick when recording a transfer.
     * Elevated roles and HR-records managers see everyone; a Subject
     * Officer sees only employees under their own effective subject codes.
     */
    private function assignableEmployees(\App\Models\User $user)
    {
        if ($user->isSuperAdmin() || $user->isPlanningOfficer() || $user->isHrRecordsManager()) {
            return Employee::active()->orderBy('name')->get(['id', 'name', 'pay_no']);
        }

        return Employee::active()
            ->whereIn('subject_code_id', $user->effectiveSubjectCodeIds())
            ->orderBy('name')
            ->get(['id', 'name', 'pay_no', 'subject_code_id']);
    }
}
