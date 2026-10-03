<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UtilityBillResponsibilityAssignment;
use App\Services\AuditLogService;
use App\Services\GovernanceAttestationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UtilityBillAdminController extends Controller
{
    public function index()
    {
        $subjectOfficers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('role', User::ROLE_SUBJECT_OFFICER))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $currentAssignment = UtilityBillResponsibilityAssignment::query()
            ->with('subjectOfficer')
            ->whereNull('ended_at')
            ->latest('effective_from')
            ->latest('id')
            ->first();

        $assignmentHistory = UtilityBillResponsibilityAssignment::query()
            ->with(['subjectOfficer', 'assignedBy'])
            ->latest('effective_from')
            ->latest('id')
            ->limit(20)
            ->get();

        $warningDays = SystemSetting::getInt('utility_bill_due_warning_days', 7);

        return view(
            'utility-bills.admin',
            compact('subjectOfficers', 'currentAssignment', 'assignmentHistory', 'warningDays')
        );
    }

    public function updateGovernance(Request $request)
    {
        $data = $request->validate([
            'subject_officer_id' => ['required', 'integer', 'exists:users,id'],
            'effective_from' => ['required', 'date', 'before_or_equal:today'],
            'reference_no' => ['required', 'string', 'max:100', 'not_regex:/^\\s/'],
            'reason' => ['required', 'string', 'min:5', 'max:1000', 'not_regex:/^\\s/'],
            'warning_days' => ['required', 'integer', 'min:1', 'max:60'],
            'authority_confirmed' => ['accepted'],
            'continuity_confirmed' => ['accepted'],
        ]);

        $attestation = [
            'administrative_purpose' => $data['reason'],
            'authority_reference' => $data['reference_no'],
            'evidence_reviewed' => true,
            'accuracy_confirmed' => true,
            'minimum_necessary_confirmed' => true,
            'no_conflict_confirmed' => true,
        ];

        unset($data['authority_confirmed'], $data['continuity_confirmed']);

        $officer = User::findOrFail((int) $data['subject_officer_id']);
        abort_unless(
            $officer->is_active && $officer->isSubjectOfficer(),
            422,
            'The selected user is not an active Subject Officer.'
        );

        DB::transaction(function () use ($data, $officer, $request) {
            $current = UtilityBillResponsibilityAssignment::query()
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($current && (int) $current->subject_officer_id !== (int) $officer->id) {
                $old = $current->getOriginal();
                $current->update([
                    'effective_to' => Carbon::parse($data['effective_from'])->subDay()->toDateString(),
                    'ended_at' => now(),
                    'ended_by' => $request->user()->id,
                    'end_reason' => 'Superseded by a new Utility Bills responsibility assignment.',
                ]);
                AuditLogService::updated(
                    $current,
                    $old,
                    'Ended prior Utility Bills Subject Officer assignment.'
                );
            }

            if (! $current || (int) $current->subject_officer_id !== (int) $officer->id) {
                $assignment = UtilityBillResponsibilityAssignment::create([
                    'subject_officer_id' => $officer->id,
                    'effective_from' => $data['effective_from'],
                    'reference_no' => $data['reference_no'] ?: null,
                    'reason' => $data['reason'],
                    'assigned_by' => $request->user()->id,
                ]);
                AuditLogService::created(
                    $assignment,
                    'Assigned responsible Subject Officer for Utility Bill management.'
                );
            }

            SystemSetting::set('utility_bill_due_warning_days', (string) $data['warning_days']);
        });

        GovernanceAttestationService::record(
            $request,
            'utility_responsibility_assignment',
            'UtilityBillResponsibilityAssignment',
            null,
            $attestation
        );

        return back()->with(
            'success',
            'Utility Bill governance settings updated with authority attestation retained.'
        );
    }
}
