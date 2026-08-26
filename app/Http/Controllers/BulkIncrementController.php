<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeIncrement;
use App\Models\Position;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bulk increment builder — a Subject Officer picks one position under
 * their own (effective) subject codes and records increment dates for
 * every active employee in that position in a single grid, rather than
 * visiting each employee's profile individually.
 *
 * Each row is independently optional — leaving a row's date blank simply
 * skips that employee; this is a convenience batch-entry tool, not an
 * all-or-nothing operation.
 */
class BulkIncrementController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $codeIds = $this->scopedSubjectCodeIds($user);

        $positions = Position::active()
            ->whereHas('employees', fn ($q) => $q->active()->whereIn('subject_code_id', $codeIds))
            ->orderBy('title')
            ->get();

        $selectedPositionId = $request->query('position_id');
        $employees = collect();

        if ($selectedPositionId) {
            $employees = Employee::active()
                ->where('position_id', $selectedPositionId)
                ->whereIn('subject_code_id', $codeIds)
                ->with(['subjectCode', 'incrementRecords' => fn ($q) => $q->active()->mostRecent()->limit(1)])
                ->orderBy('name')
                ->get();
        }

        return view('bulk-increments.create', [
            'positions'           => $positions,
            'selectedPositionId'  => $selectedPositionId,
            'employees'           => $employees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'position_id'          => ['required', 'integer', 'exists:positions,id'],
            'employee_id'          => ['required', 'array'],
            'employee_id.*'        => ['integer', 'exists:employees,id'],
            'increment_date'       => ['required', 'array'],
            'increment_date.*'     => ['nullable', 'date'],
            'amount'                => ['nullable', 'array'],
            'amount.*'              => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'reference_no'          => ['nullable', 'array'],
            'reference_no.*'        => ['nullable', 'string', 'max:60'],
        ]);

        $allowedCodes = $this->scopedSubjectCodeIds($user);
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($data, $user, $allowedCodes, &$created, &$skipped) {
            foreach ($data['employee_id'] as $i => $employeeId) {
                $date = $data['increment_date'][$i] ?? null;
                if (! $date) {
                    $skipped++;
                    continue; // row left blank — intentionally skipped
                }

                $employee = Employee::find($employeeId);

                // Re-verify scope per row server-side — never trust the
                // employee_id list from the client alone, even though the
                // form was built from a pre-scoped query.
                if (! $employee || ! $allowedCodes->contains($employee->subject_code_id)) {
                    $skipped++;
                    continue;
                }

                EmployeeIncrement::create([
                    'employee_id'    => $employee->id,
                    'increment_date' => $date,
                    'amount'         => $data['amount'][$i] ?? null,
                    'reference_no'   => $data['reference_no'][$i] ?? null,
                    'recorded_by'    => $user->id,
                    'is_active'      => true,
                ]);
                $created++;
            }
        });

        $position = Position::find($data['position_id']);
        AuditLogService::created(
            new EmployeeIncrement(),
            "Bulk increment entry: {$created} record(s) created for position {$position?->title} by {$user->name}"
        );

        return redirect()->route('bulk-increments.create', ['position_id' => $data['position_id']])
            ->with('success', "{$created} increment record(s) saved" . ($skipped > 0 ? ", {$skipped} row(s) skipped (left blank)." : '.'));
    }

    /**
     * Subject codes this user may act on for the bulk builder.
     * Super Admin / Planning Officer -> every active subject code (they
     * oversee the whole establishment, matching the identical pattern in
     * EmployeeController::assignableCodes()).
     * Subject Officer -> only their effective (permanent + acting) codes.
     *
     * NOTE: relying on User::effectiveSubjectCodeIds() alone here would be
     * a real bug, not just a defense-in-depth gap — that method is
     * deliberately Subject-Officer-scoped and returns an EMPTY collection
     * for a Super Admin/Planning Officer with no personal subject code
     * assignment, which would silently break this feature for them
     * (zero positions shown, every row rejected) rather than granting the
     * broader access their role is supposed to have.
     */
    private function scopedSubjectCodeIds(\App\Models\User $user): \Illuminate\Support\Collection
    {
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) {
            return \App\Models\SubjectCode::active()->pluck('id');
        }

        abort_unless($user->isSubjectOfficer(), 403, 'You do not have permission to record increments.');

        return $user->effectiveSubjectCodeIds();
    }
}
