<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActingAppointment;
use App\Models\CarderMonthlyEntry;
use App\Models\EmployeeIncrement;
use App\Models\RetirementProject;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;

class WorkforceActionCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $employees = WorkforceScopeService::employeeQuery($user)->where('is_active', true)->get(['id', 'subject_code_id', 'position_id', 'unit_id', 'date_of_birth', 'retirement_age']);
        $ids = $employees->pluck('id');
        $now = now();

        // Refresh quality detection so the Action Centre never depends on someone opening Data Quality first.
        $detected = app(DataQualityController::class)->detectIssues($employees);
        $quality = $detected->sortBy(fn ($i) => match ($i['severity']) {
            'critical' => 1,'high' => 2,'medium' => 3,default => 4
        });

        $increments = EmployeeIncrement::active()->whereIn('employee_id', $ids)
            ->with('employee.position', 'employee.unit')
            ->whereNotIn('workflow_status', ['granted', 'deferred', 'withheld'])
            ->whereDate('increment_date', '<=', $now->copy()->addDays(30)->toDateString())
            ->orderBy('increment_date')->get();

        $retirements = RetirementProject::whereIn('employee_id', $ids)
            ->with('employee.position', 'employee.unit')
            ->where('status', '<>', 'completed')->orderBy('retirement_date')->get();

        $acting = ActingAppointment::whereIn('employee_id', $ids)->where('is_active', true)
            ->whereNotNull('end_date')->whereDate('end_date', '<=', $now->copy()->addDays(30)->toDateString())
            ->with('employee.position', 'actingPosition')->orderBy('end_date')->get();

        $pendingVerification = collect();
        $amendments = collect();
        if ($user->isSuperAdmin() || $user->isPlanningOfficer() || $user->canVerifyEntries()) {
            $pendingVerification = CarderMonthlyEntry::pendingVerification()->with(['subjectCode', 'position', 'submittedBy'])->latest('submitted_at')->limit(20)->get();
            $amendments = CarderMonthlyEntry::amendmentRequested()->with(['subjectCode', 'position', 'amendmentRequestedBy'])->latest('amendment_requested_at')->limit(20)->get();
        }

        $counts = [
            'critical_quality' => $quality->where('severity', 'critical')->count(),
            'overdue_increments' => $increments->filter(fn ($i) => $i->increment_date?->isPast())->count(),
            'increments_30' => $increments->count(),
            'retirements' => $retirements->count(),
            'acting_expiring' => $acting->count(),
            'verification' => $pendingVerification->count(),
            'amendments' => $amendments->count(),
        ];

        $isScoped = $user->isSubjectOfficer() && ! $user->isSuperAdmin() && ! $user->isPlanningOfficer();

        return view('workforce.action-center', compact('counts', 'quality', 'increments', 'retirements', 'acting', 'pendingVerification', 'amendments', 'isScoped'));
    }
}
