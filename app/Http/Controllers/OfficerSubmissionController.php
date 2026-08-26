<?php

namespace App\Http\Controllers;

use App\Models\CarderMonthlyEntry;
use App\Models\Position;
use Illuminate\Http\Request;

/**
 * Admin Group + Super Admin — read-only view showing which Subject Officers
 * have (or haven't) submitted their monthly carder entries for the selected
 * period, broken down by Position → Subject Code → Officer.
 */
class OfficerSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        // Look at 6 rolling months for the trend columns.
        $periods = collect(range(5, 0))->map(function ($offset) use ($year, $month) {
            $date = now()->setYear($year)->setMonth($month)->subMonths($offset)->startOfMonth();
            return ['year' => (int) $date->format('Y'), 'month' => (int) $date->format('n'), 'label' => $date->format('M Y')];
        })->values();

        // All submitted entries for the 6-month window.
        $existingEntries = CarderMonthlyEntry::whereIn('year', $periods->pluck('year')->unique())
            ->whereIn('month', $periods->pluck('month')->unique())
            ->get(['subject_code_id', 'year', 'month', 'males', 'females', 'no_pay_leave', 'submitted_by', 'submitted_at'])
            ->groupBy(fn ($e) => "{$e->subject_code_id}-{$e->year}-{$e->month}");

        // For each period we also need to know the last-known entry per code
        // (carry-forward source) so we can show "as of March" on carried cells.
        $latestPerCode = CarderMonthlyEntry::selectRaw('DISTINCT ON (subject_code_id) *')
            ->orderBy('subject_code_id')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->keyBy('subject_code_id');

        $positions = Position::active()
            ->with(['subjectCodes' => function ($q) {
                $q->active()->with(['users' => function ($uq) {
                    $uq->where('is_active', true)->havingRole('subject_officer')->with('category');
                }]);
            }])
            ->orderBy('title')
            ->get();

        // Overall submission stats for the KPI header.
        $totalSlots    = 0;
        $submittedSlots = 0;

        $positionData = $positions->map(function ($position) use ($periods, $existingEntries, $latestPerCode, &$totalSlots, &$submittedSlots) {
            $codeRows = $position->subjectCodes->map(function ($code) use ($periods, $existingEntries, $latestPerCode, &$totalSlots, &$submittedSlots) {
                $officerNames = $code->users->pluck('name')->join(', ') ?: '—';

                $periodStatus = $periods->map(function ($period) use ($code, $existingEntries, $latestPerCode, &$totalSlots, &$submittedSlots) {
                    $key   = "{$code->id}-{$period['year']}-{$period['month']}";
                    $entry = $existingEntries->get($key)?->first();

                    $periodOrdinal = $period['year'] * 12 + $period['month'];

                    if ($code->users->isNotEmpty()) {
                        $totalSlots++;
                    }

                    if ($entry) {
                        // Actual submission for this exact period.
                        if ($code->users->isNotEmpty()) $submittedSlots++;
                        return [
                            'status'       => 'submitted',
                            'total'        => $entry->males + $entry->females + $entry->no_pay_leave,
                            'no_pay_leave' => $entry->no_pay_leave,
                            'submitted_at' => $entry->submitted_at,
                        ];
                    }

                    // Check if there is an earlier entry to carry forward from.
                    $last = $latestPerCode->get($code->id);
                    $lastOrdinal = $last ? ($last->year * 12 + $last->month) : null;

                    if ($last && $lastOrdinal < $periodOrdinal) {
                        // Carried forward — count it as submitted (data is available).
                        if ($code->users->isNotEmpty()) $submittedSlots++;
                        return [
                            'status'            => 'carried',
                            'total'             => $last->males + $last->females + $last->no_pay_leave,
                            'no_pay_leave'      => $last->no_pay_leave,
                            'carried_from_year' => $last->year,
                            'carried_from_month'=> $last->month,
                        ];
                    }

                    return ['status' => $code->users->isEmpty() ? 'no_officer' : 'missing'];
                });

                return (object) [
                    'code'         => $code->code,
                    'name'         => $code->name,
                    'officers'     => $officerNames,
                    'periods'      => $periodStatus,
                ];
            });

            return (object) ['title' => $position->title, 'codes' => $codeRows];
        })->filter(fn ($p) => $p->codes->isNotEmpty());

        $submissionRate = $totalSlots > 0 ? round(($submittedSlots / $totalSlots) * 100) : 0;

        return view('officer-submissions.index', compact(
            'positionData', 'periods', 'year', 'month',
            'totalSlots', 'submittedSlots', 'submissionRate'
        ));
    }
}
