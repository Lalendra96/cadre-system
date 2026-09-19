<?php

namespace App\Http\Controllers;

use App\Models\ApprovedCarder;
use App\Models\CarderMonthlyEntry;
use App\Models\Position;
use App\Models\Employee;
use App\Models\TransferRecord;
use App\Models\EmployeeServicePeriod;
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
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $approved = ApprovedCarder::forYear($year)
            ->get()
            ->groupBy('position_id')
            ->map(fn ($r) => ['amount' => $r->sum('approved_amount'), 'carder' => $r->first()]);

        // Carry-forward: use last known figures when no entry for this period.
        $actuals = CarderMonthlyEntry::forPeriodWithCarryForward($year, $month)
            ->groupBy('position_id')
            ->map(fn ($rows) => [
                'males'            => $rows->sum('males'),
                'females'          => $rows->sum('females'),
                'total'            => $rows->sum('males') + $rows->sum('females') + $rows->sum('no_pay_leave'),
                'no_pay_leave'     => $rows->sum('no_pay_leave'),
                'carried_forward'  => $rows->contains('is_carried_forward', true),
            ]);

        $positions = Position::active()->orderBy('title')->get();

        $rows = $positions->map(function ($p) use ($approved, $actuals) {
            // Use a safe default so $ap is always a populated array — never
            // null — even for positions that have no approved carder row yet.
            $ap = $approved->get($p->id, ['amount' => 0, 'carder' => null]);
            $ac = $actuals->get($p->id, [
                'males' => 0, 'females' => 0, 'total' => 0,
                'no_pay_leave' => 0, 'carried_forward' => false,
            ]);

            $approvedTotal = (int) $ap['amount'];
            $vacancy       = max($approvedTotal - $ac['total'], 0);

            return (object) [
                'id'           => $p->id,
                'title'        => $p->title,
                'approved'     => $approvedTotal,
                'males'        => $ac['males'],
                'females'      => $ac['females'],
                'in_position'  => $ac['total'],
                'vacancy'      => $vacancy,
                'fill_pct'     => $approvedTotal > 0 ? round(($ac['total'] / $approvedTotal) * 100) : 0,
                'no_pay_leave' => $ac['no_pay_leave'],
                'carried'      => $ac['carried_forward'],
                'carder_id'    => $ap['carder']?->id,
            ];
        });

        $kpis = [
            'total_approved'   => $rows->sum('approved'),
            'total_filled'     => $rows->sum('in_position'),
            'total_vacancy'    => $rows->sum('vacancy'),
            'positions_zero'   => $rows->where('in_position', 0)->count(),
            'fill_pct_overall' => $rows->sum('approved') > 0
                ? round(($rows->sum('in_position') / $rows->sum('approved')) * 100)
                : 0,
        ];

        $years = ApprovedCarder::select('year')->distinct()->orderByDesc('year')->pluck('year');

        // Institution-level movement and progression signals. These are planning aggregates;
        // Admin Group never receives individual Employee Profile links from this page.
        $yearStart = now()->copy()->startOfYear();
        $incoming = TransferRecord::active()->where('direction','in')->where('effective_date','>=',$yearStart)->count();
        $outgoing = TransferRecord::active()->where('direction','out')->where('effective_date','>=',$yearStart)->count();
        $combinedIncoming = EmployeeServicePeriod::active()->where('is_current',true)
            ->where('movement_type','incoming_external_transfer')->whereNotNull('service_name')
            ->where('start_date','>=',$yearStart)->count();
        $retirements12 = Employee::active()->get()->filter(fn($e) => $e->retire_date && $e->retire_date->between(now(), now()->copy()->addMonths(12)))->count();
        $futureOutgoing90 = TransferRecord::active()->where('direction','out')->whereBetween('effective_date',[now()->toDateString(),now()->copy()->addDays(90)->toDateString()])->count();
        $gradeDue12 = Employee::active()->with('gradeRecords.positionGrade')->get()->filter(function($e){
            $current=$e->current_grade; $next=$current?->positionGrade?->nextGrade(); $years=$next?->minYearsInGrade();
            if(!$current || !$current->effective_date || $years===null) return false;
            return $current->effective_date->copy()->addDays((int) round($years*365.25))->lte(now()->copy()->addMonths(12));
        })->count();
        $movementKpis = [
            'incoming'=>$incoming,'outgoing'=>$outgoing,'net'=>$incoming-$outgoing,'combined_incoming'=>$combinedIncoming,
            'future_outgoing_90'=>$futureOutgoing90,'retirements_12'=>$retirements12,'grade_due_12'=>$gradeDue12,
        ];

        return view('planning.summary', compact('rows', 'kpis', 'year', 'month', 'years', 'movementKpis'));
    }
}
