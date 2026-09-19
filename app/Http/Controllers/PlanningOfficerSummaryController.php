<?php

namespace App\Http\Controllers;

use App\Models\ApprovedCarder;
use App\Models\CarderMonthlyEntry;
use App\Models\Employee;
use App\Models\EmployeeServicePeriod;
use App\Models\Position;
use App\Models\TransferRecord;
use Illuminate\Http\Request;

/**
 * Planning Officer summary — combines approved carder figures with the
 * latest actual headcount (carry-forward applied) so the Planning Officer
 * can see at a glance which positions have the biggest vacancies and jump
 * straight to editing them.
 */
class PlanningOfficerSummaryController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $approved = ApprovedCarder::forYear($year)
            ->get()
            ->groupBy('position_id')
            ->map(
                fn ($rows) => [
                    'amount' => $rows->sum('approved_amount'),
                    'carder' => $rows->first(),
                ]
            );

        // Carry-forward: use last known figures when no entry for this period.
        $actuals = CarderMonthlyEntry::forPeriodWithCarryForward(
            $year,
            $month
        )
            ->groupBy('position_id')
            ->map(
                fn ($rows) => [
                    'males' => $rows->sum('males'),
                    'females' => $rows->sum('females'),
                    'total' => $rows->sum('males')
                        + $rows->sum('females')
                        + $rows->sum('no_pay_leave'),
                    'no_pay_leave' => $rows->sum('no_pay_leave'),
                    'carried_forward' => $rows->contains(
                        'is_carried_forward',
                        true
                    ),
                ]
            );

        $positions = Position::active()
            ->orderBy('title')
            ->get();

        $rows = $positions->map(
            function ($position) use ($approved, $actuals) {
                $approvedData = $approved->get(
                    $position->id,
                    [
                        'amount' => 0,
                        'carder' => null,
                    ]
                );

                $actualData = $actuals->get(
                    $position->id,
                    [
                        'males' => 0,
                        'females' => 0,
                        'total' => 0,
                        'no_pay_leave' => 0,
                        'carried_forward' => false,
                    ]
                );

                $approvedTotal = (int) $approvedData['amount'];
                $vacancy = max(
                    $approvedTotal - $actualData['total'],
                    0
                );

                return (object) [
                    'id' => $position->id,
                    'title' => $position->title,
                    'approved' => $approvedTotal,
                    'males' => $actualData['males'],
                    'females' => $actualData['females'],
                    'in_position' => $actualData['total'],
                    'vacancy' => $vacancy,
                    'fill_pct' => $approvedTotal > 0
                        ? round(
                            ($actualData['total'] / $approvedTotal) * 100
                        )
                        : 0,
                    'no_pay_leave' => $actualData['no_pay_leave'],
                    'carried' => $actualData['carried_forward'],
                    'carder_id' => $approvedData['carder']?->id,
                ];
            }
        );

        $kpis = [
            'total_approved' => $rows->sum('approved'),
            'total_filled' => $rows->sum('in_position'),
            'total_vacancy' => $rows->sum('vacancy'),
            'positions_zero' => $rows
                ->where('in_position', 0)
                ->count(),
            'fill_pct_overall' => $rows->sum('approved') > 0
                ? round(
                    (
                        $rows->sum('in_position')
                        / $rows->sum('approved')
                    ) * 100
                )
                : 0,
        ];

        $years = ApprovedCarder::select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $yearStart = now()->copy()->startOfYear();

        $incoming = TransferRecord::active()
            ->where('direction', 'in')
            ->where('effective_date', '>=', $yearStart)
            ->count();

        $outgoing = TransferRecord::active()
            ->where('direction', 'out')
            ->where('effective_date', '>=', $yearStart)
            ->count();

        $combinedIncoming = EmployeeServicePeriod::active()
            ->where('is_current', true)
            ->where(
                'movement_type',
                'incoming_external_transfer'
            )
            ->whereNotNull('service_name')
            ->where('start_date', '>=', $yearStart)
            ->count();

        $retirements12 = Employee::active()
            ->get()
            ->filter(
                fn ($employee) => $employee->retire_date
                    && $employee->retire_date->between(
                        now(),
                        now()->copy()->addMonths(12)
                    )
            )
            ->count();

        $futureOutgoing90 = TransferRecord::active()
            ->where('direction', 'out')
            ->whereBetween(
                'effective_date',
                [
                    now()->toDateString(),
                    now()->copy()
                        ->addDays(90)
                        ->toDateString(),
                ]
            )
            ->count();

        $gradeDue12 = Employee::active()
            ->with('gradeRecords.positionGrade')
            ->get()
            ->filter(
                function ($employee) {
                    $current = $employee->current_grade;
                    $next = $current?->positionGrade?->nextGrade();
                    $requiredYears = $next?->minYearsInGrade();

                    if (
                        ! $current
                        || ! $current->effective_date
                        || $requiredYears === null
                    ) {
                        return false;
                    }

                    return $current->effective_date
                        ->copy()
                        ->addDays(
                            (int) round(
                                $requiredYears * 365.25
                            )
                        )
                        ->lte(
                            now()->copy()->addMonths(12)
                        );
                }
            )
            ->count();

        $movementKpis = [
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'net' => $incoming - $outgoing,
            'combined_incoming' => $combinedIncoming,
            'future_outgoing_90' => $futureOutgoing90,
            'retirements_12' => $retirements12,
            'grade_due_12' => $gradeDue12,
        ];

        return view(
            'planning.summary',
            compact(
                'rows',
                'kpis',
                'year',
                'month',
                'years',
                'movementKpis'
            )
        );
    }
}
