<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CarderMonthlyEntry;
use App\Models\EmployeeIncrement;
use App\Models\EmployeeChangeRequest;
use App\Models\ServiceLetter;
use App\Models\TransferRecord;
use App\Models\Position;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectOfficerWorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $employees = WorkforceScopeService::employeeQuery($user)
            ->where('is_active', true)
            ->with(['position', 'unit', 'subjectCode', 'servicePeriods'])
            ->get();
        $employeeIds = $employees->pluck('id');
        $codeIds = $user->effectiveSubjectCodeIds();
        $hrPositions = Position::whereIn('id', $user->effectiveHrPositionIds())
            ->orderBy('title')
            ->get(['id', 'title']);
        $now = now();
        $currentEntry = CarderMonthlyEntry::whereIn('subject_code_id', $codeIds)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->latest('submitted_at')
            ->first();
        $increments = EmployeeIncrement::active()
            ->whereIn('employee_id', $employeeIds)
            ->orderBy('increment_date')
            ->get();
        $due30 = $increments
            ->filter(
                fn ($increment) => $increment->increment_date &&
                    $increment->increment_date->between(
                        $now->copy()->startOfDay(),
                        $now->copy()->addDays(30)->endOfDay(),
                    ),
            )
            ->count();
        $overdue = $increments
            ->filter(
                fn ($increment) => $increment->increment_date &&
                    $increment->increment_date->isPast() &&
                    !in_array($increment->workflow_status, ['granted', 'deferred', 'withheld'], true),
            )
            ->count();
        $retirements = $employees
            ->filter(
                fn ($employee) => $employee->retire_date &&
                    $employee->retire_date->between($now, $now->copy()->addMonths(12)),
            )
            ->sortBy('retire_date');
        $qualityIssues = app(DataQualityController::class)->detectIssues($employees)->count();
        $actingEnding = $employees->sum(
            fn ($employee) => $employee
                ->actingAppointments()
                ->whereDate('end_date', '>=', $now->toDateString())
                ->whereDate('end_date', '<=', $now->copy()->addDays(30)->toDateString())
                ->count(),
        );

        // Value-added Subject Officer signals. Every metric is restricted to the
        // officer's allocated employee IDs; shared positions never broaden access.
        $serviceLetters = ServiceLetter::whereIn('employee_id', $employeeIds)
            ->where('is_active', true)
            ->get(['id', 'status']);
        $letterStats = [
            'draft' => $serviceLetters->where('status', ServiceLetter::STATUS_DRAFT)->count(),
            'pending' => $serviceLetters->where('status', ServiceLetter::STATUS_PENDING_APPROVAL)->count(),
            'rejected' => $serviceLetters->where('status', ServiceLetter::STATUS_REJECTED)->count(),
        ];

        $pendingCorrections = EmployeeChangeRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'pending')->count();

        $registrationsExpiring = $employees->filter(
            fn ($employee) => $employee->professional_registration_expiry
                && $employee->professional_registration_expiry->between(
                    $now->copy()->startOfDay(),
                    $now->copy()->addDays(90)->endOfDay(),
                ),
        )->sortBy('professional_registration_expiry');

        // Grade/position progression is treated as the primary career reminder.
        // Eligibility is derived from the configured position grade ladder and
        // min_years_in_grade on the target grade. The employee scope remains
        // allocation-based through $employees above.
        $gradePromotionReminders = $employees->map(function ($employee) use ($now) {
            $current = $employee->current_grade;
            $next = $current?->positionGrade?->nextGrade();
            $years = $next?->minYearsInGrade();
            if (! $current || ! $next || $years === null || ! $current->effective_date) {
                return null;
            }
            $eligibleOn = $current->effective_date->copy()->addDays((int) round($years * 365.25));
            if ($eligibleOn->gt($now->copy()->addMonths(6))) {
                return null;
            }
            return (object) [
                'employee' => $employee,
                'current_grade' => $current->positionGrade?->name ?? 'Current grade',
                'next_grade' => $next->name,
                'eligible_on' => $eligibleOn,
                'eligible_now' => $eligibleOn->lte($now),
            ];
        })->filter()->sortBy(fn ($row) => $row->eligible_on);

        $gradePromotionEligibleNow = $gradePromotionReminders->where('eligible_now', true)->count();

        $unconfirmed = $employees->where('is_confirmed', false)->count();
        $incompleteServiceHistory = $employees->filter(fn ($employee) => ! $employee->date_joined_public_service || $employee->servicePeriods->where('is_active', true)->isEmpty())->count();
        $recentTransfers = TransferRecord::whereIn('employee_id', $employeeIds)
            ->where('is_active', true)
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->count();

        $positionWorkload = $employees->groupBy('position_id')->map(function ($group) {
            $position = $group->first()?->position;
            return (object) [
                'position' => $position?->title ?? 'Unassigned position',
                'count' => $group->count(),
            ];
        })->sortByDesc('count')->values();

        return view(
            'subject-officer.workspace',
            compact(
                'employees',
                'currentEntry',
                'due30',
                'overdue',
                'retirements',
                'qualityIssues',
                'actingEnding',
                'hrPositions',
                'letterStats',
                'pendingCorrections',
                'registrationsExpiring',
                'gradePromotionReminders',
                'gradePromotionEligibleNow',
                'unconfirmed',
                'incompleteServiceHistory',
                'recentTransfers',
                'positionWorkload',
            ),
        );
    }
}
