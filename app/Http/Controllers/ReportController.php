<?php

namespace App\Http\Controllers;

use App\Models\ApprovedCarder;
use App\Models\Employee;
use App\Services\AuditLogService;
use App\Services\PdfExportService;
use App\Models\CarderMonthlyEntry;
use App\Models\Position;
use Illuminate\Http\Request;

/**
 * Admin Group + Super Admin — read-only summarised view with charts and
 * a custom chart builder. Tracking is at Position level.
 */
class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $years = ApprovedCarder::select('year')->distinct()->orderByDesc('year')->pluck('year');

        $approved = ApprovedCarder::forYear($year)->get()
            ->groupBy('position_id')
            ->map(fn ($rows) => $rows->sum('approved_amount'));

        // Use carry-forward: if a subject code has no entry for the requested
        // period, the most recent prior entry is used as-is.
        $actuals = CarderMonthlyEntry::forPeriodWithCarryForward($year, $month)
            ->groupBy('position_id')
            ->map(fn ($rows) => [
                'available_male'   => $rows->sum('males'),
                'available_female' => $rows->sum('females'),
                'transferred_in'   => $rows->sum('transferred_in'),
                'transferred_out'  => $rows->sum('transferred_out'),
                'no_pay_leave'     => $rows->sum('no_pay_leave'),
                'has_carried'      => $rows->contains('is_carried_forward', true),
            ]);

        $positions = Position::active()->orderBy('title')->get();

        $rows = $positions->map(function ($p) use ($approved, $actuals) {
            $approvedTotal = $approved->get($p->id, 0);
            $c = $actuals->get($p->id, [
                'available_male' => 0, 'available_female' => 0,
                'transferred_in' => 0, 'transferred_out' => 0, 'no_pay_leave' => 0,
                'has_carried' => false,
            ]);

            $availableTotal = $c['available_male'] + $c['available_female'] + $c['no_pay_leave'];

            return (object) [
                'position'         => $p->title,
                'approved_total'   => $approvedTotal,
                'available_male'   => $c['available_male'],
                'available_female' => $c['available_female'],
                'available_total'  => $availableTotal,
                'vacancy'          => max($approvedTotal - $availableTotal, 0),
                'transferred_in'   => $c['transferred_in'],
                'transferred_out'  => $c['transferred_out'],
                'no_pay_leave'     => $c['no_pay_leave'],
                'carried_forward'  => $c['has_carried'],  // true = some codes used prior-period data
            ];
        });

        $kpis = [
            'approved_total'  => $rows->sum('approved_total'),
            'available_total' => $rows->sum('available_total'),
            'vacancy_total'   => $rows->sum('vacancy'),
            'no_pay_leave'    => $rows->sum('no_pay_leave'),
        ];

        return view('reports.summary', compact('rows', 'kpis', 'year', 'month', 'years'));
    }

    public function chartData(Request $request)
    {
        $validated = $request->validate([
            'year'   => ['required', 'integer', 'min:2000', 'max:2100'],
            'month'  => ['nullable', 'integer', 'min:1', 'max:12'],
            'metric' => ['required', 'in:approved_vs_available,vacancy_by_position,no_pay_leave_trend'],
        ]);

        $year  = $validated['year'];
        $month = $validated['month'] ?? now()->month;

        if ($validated['metric'] === 'approved_vs_available') {
            $positions = Position::active()->orderBy('title')->get(['id', 'title']);

            $approved  = ApprovedCarder::forYear($year)->get()
                ->groupBy('position_id')->map(fn ($r) => $r->sum('approved_amount'));

            $available = CarderMonthlyEntry::forPeriodWithCarryForward($year, $month)
                ->groupBy('position_id')
                ->map(fn ($r) => $r->sum('males') + $r->sum('females') + $r->sum('no_pay_leave'));

            return response()->json([
                'labels'    => $positions->pluck('title'),
                'approved'  => $positions->map(fn ($p) => $approved->get($p->id, 0)),
                'available' => $positions->map(fn ($p) => $available->get($p->id, 0)),
            ]);
        }

        if ($validated['metric'] === 'vacancy_by_position') {
            $positions = Position::active()->orderBy('title')->get(['id', 'title']);

            $approved  = ApprovedCarder::forYear($year)->get()->groupBy('position_id')
                ->map(fn ($r) => $r->sum('approved_amount'));
            $available = CarderMonthlyEntry::forPeriodWithCarryForward($year, $month)
                ->groupBy('position_id')
                ->map(fn ($r) => $r->sum('males') + $r->sum('females') + $r->sum('no_pay_leave'));

            return response()->json([
                'labels'  => $positions->pluck('title'),
                'vacancy' => $positions->map(fn ($p) => max(($approved->get($p->id, 0)) - ($available->get($p->id, 0)), 0)),
            ]);
        }

        $monthly = CarderMonthlyEntry::forPeriod($year)->get()->groupBy('month')
            ->map(fn ($r) => $r->sum('no_pay_leave'));

        return response()->json([
            'labels'       => collect(range(1, 12))->map(fn ($m) => date('M', mktime(0, 0, 0, $m, 1))),
            'no_pay_leave' => collect(range(1, 12))->map(fn ($m) => $monthly->get($m, 0)),
        ]);
    }

// ════════════════════════════════════════════════════════════════════
    // Feature 11: 12-Month Trend per Position
    // ════════════════════════════════════════════════════════════════════

    public function trend(Request $request)
    {
        $positionId = $request->query('position_id');
        $year       = (int) $request->query('year', now()->year);
        $positions  = \App\Models\Position::active()->orderBy('title')->get(['id','title']);

        $trendData = null;
        if ($positionId) {
            $position  = \App\Models\Position::findOrFail($positionId);
            $approved  = \App\Models\ApprovedCarder::forYear($year)->where('position_id', $positionId)->sum('approved_amount');
            $months    = collect(range(1,12))->map(fn ($m) => [
                'month'     => $m,
                'label'     => \DateTime::createFromFormat('!m', $m)->format('M'),
                'in_position' => 0,
                'vacancy'     => $approved,
            ]);

            $entries = \App\Models\CarderMonthlyEntry::where('position_id', $positionId)
                ->where('year', $year)
                ->get()
                ->keyBy('month');

            $months = $months->map(function ($m) use ($entries, $approved) {
                $e = $entries->get($m['month']);
                $inPos = $e ? ($e->males + $e->females + $e->no_pay_leave) : null;
                return [...$m, 'in_position' => $inPos, 'vacancy' => $inPos !== null ? max($approved - $inPos, 0) : null, 'no_pay_leave' => $e?->no_pay_leave];
            });

            $trendData = compact('position','months','approved','year');
        }

        return view('reports.trend', compact('positions','trendData','positionId','year'));
    }

    // ════════════════════════════════════════════════════════════════════
    // Feature 12: Year-on-Year Comparison
    // ════════════════════════════════════════════════════════════════════

    public function yoy(Request $request)
    {
        $year1    = (int) $request->query('year1', now()->year - 1);
        $year2    = (int) $request->query('year2', now()->year);
        $month    = (int) $request->query('month', now()->month);
        $positions = \App\Models\Position::active()->orderBy('title')->get(['id','title']);

        $act1 = \App\Models\CarderMonthlyEntry::forPeriodWithCarryForward($year1, $month)
            ->groupBy('position_id')
            ->map(fn ($r) => $r->sum('males') + $r->sum('females') + $r->sum('no_pay_leave'));

        $act2 = \App\Models\CarderMonthlyEntry::forPeriodWithCarryForward($year2, $month)
            ->groupBy('position_id')
            ->map(fn ($r) => $r->sum('males') + $r->sum('females') + $r->sum('no_pay_leave'));

        $ap1 = \App\Models\ApprovedCarder::forYear($year1)->get()->groupBy('position_id')->map(fn ($r) => $r->sum('approved_amount'));
        $ap2 = \App\Models\ApprovedCarder::forYear($year2)->get()->groupBy('position_id')->map(fn ($r) => $r->sum('approved_amount'));

        $rows = $positions->map(fn ($p) => (object)[
            'title'        => $p->title,
            'approved1'    => $ap1->get($p->id, 0),
            'in_pos1'      => $act1->get($p->id, 0),
            'approved2'    => $ap2->get($p->id, 0),
            'in_pos2'      => $act2->get($p->id, 0),
            'change_in_pos'=> $act2->get($p->id, 0) - $act1->get($p->id, 0),
        ]);

        return view('reports.yoy', compact('rows','year1','year2','month','positions'));
    }

    // ════════════════════════════════════════════════════════════════════
    // Feature 13: Historical Snapshot
    // ════════════════════════════════════════════════════════════════════

    public function snapshot(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        [$rows] = $this->buildSnapshotRows($year, $month);

        return view('reports.snapshot', compact('rows','year','month'));
    }

    // ════════════════════════════════════════════════════════════════════
    // Feature 3: MoH Format Exports
    // ════════════════════════════════════════════════════════════════════

    public function exportMohCsv(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        [$rows] = $this->buildSnapshotRows($year, $month);

        // Log export audit
        \App\Services\AuditLogService::logExport(
            $request->user()->id, 'csv_export',
            "carder-summary-{$year}-{$month}.csv",
            'CarderMonthlyEntry', null, null,
            $request->ip(), $request->userAgent()
        );

        $filename = "moh-carder-{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($rows, $year, $month) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Institution Carder Summary', '', '', '', '', '', '', '']);
            fputcsv($handle, ['Period:', \DateTime::createFromFormat('!m', $month)->format('F') . ' ' . $year, '', '', '', '', '', '']);
            fputcsv($handle, ['Generated:', now()->format('d M Y H:i'), '', '', '', '', '', '']);
            fputcsv($handle, []);
            fputcsv($handle, ['No.','Position','Approved','Male','Female','In Position','Vacancy','No-Pay Leave']);
            foreach ($rows as $i => $r) {
                fputcsv($handle, [$i+1, $r->title, $r->approved, $r->males, $r->females, $r->in_position, $r->vacancy, $r->no_pay]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Total','', $rows->sum('approved'),'', '', $rows->sum('in_position'), $rows->sum('vacancy'), $rows->sum('no_pay')]);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportMohPrint(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        [$rows] = $this->buildSnapshotRows($year, $month);

        \App\Services\AuditLogService::logExport(
            $request->user()->id, 'pdf_report',
            "moh-carder-{$year}-{$month}.pdf",
            null, null, null, $request->ip(), $request->userAgent()
        );

        return view('reports.moh-print', compact('rows','year','month'));
    }

    public function exportCarderRegister(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $positions = \App\Models\Position::active()->orderBy('title')
            ->with(['subjectCodes.users', 'approvedCarders' => fn ($q) => $q->where('year', $year)])
            ->get();

        \App\Services\AuditLogService::logExport(
            $request->user()->id, 'pdf_report',
            "carder-register-{$year}.pdf",
            null, null, null, $request->ip(), $request->userAgent()
        );

        return view('reports.carder-register-print', compact('positions','year'));
    }

    // ════════════════════════════════════════════════════════════════════
    // Feature 5: Retirement Projections
    // ════════════════════════════════════════════════════════════════════

    public function retirementProjections(Request $request)
    {
        $horizon = (int) $request->query('months', 24);

        $employees = \App\Models\Employee::whereNotNull('date_of_birth')
            ->with(['position','subjectCode'])
            ->get()
            ->map(function ($e) use ($horizon) {
                $retireAge  = $e->retirement_age ?? 60;
                $dob        = $e->date_of_birth;
                $retireDate = $dob->copy()->addYears($retireAge);
                $monthsLeft = now()->diffInMonths($retireDate, false);
                $ageNow     = now()->diffInYears($dob);
                return (object)[
                    'id'           => $e->id,
                    'name'         => $e->name,
                    'pay_no'       => $e->pay_no,
                    'position'     => $e->position?->title ?? '—',
                    'dob'          => $dob->format('d M Y'),
                    'age'          => $ageNow,
                    'retire_date'  => $retireDate->format('d M Y'),
                    'months_left'  => $monthsLeft,
                    'status'       => $monthsLeft < 0 ? 'overdue' : ($monthsLeft <= 6 ? 'critical' : ($monthsLeft <= 12 ? 'soon' : ($monthsLeft <= $horizon ? 'upcoming' : 'future'))),
                ];
            })
            ->filter(fn ($e) => $e->months_left <= $horizon)
            ->sortBy('months_left');

        return view('reports.retirement-projections', compact('employees','horizon'));
    }

    // ── Shared helper ────────────────────────────────────────────────────

    private function buildSnapshotRows(int $year, int $month): array
    {
        $approved = \App\Models\ApprovedCarder::forYear($year)->get()
            ->groupBy('position_id')->map(fn ($r) => $r->sum('approved_amount'));

        $actuals = \App\Models\CarderMonthlyEntry::forPeriodWithCarryForward($year, $month)
            ->groupBy('position_id')->map(fn ($rows) => [
                'total'   => $rows->sum('males') + $rows->sum('females') + $rows->sum('no_pay_leave'),
                'males'   => $rows->sum('males'),
                'females' => $rows->sum('females'),
                'no_pay'  => $rows->sum('no_pay_leave'),
                'carried' => $rows->contains('is_carried_forward', true),
            ]);

        $positions = \App\Models\Position::active()->orderBy('title')->get();
        $rows = $positions->map(fn ($p) => (object)[
            'title'       => $p->title,
            'approved'    => $approved->get($p->id, 0),
            'in_position' => $actuals->get($p->id, ['total'=>0])['total'],
            'males'       => $actuals->get($p->id, ['males'=>0])['males'],
            'females'     => $actuals->get($p->id, ['females'=>0])['females'],
            'vacancy'     => max(($approved->get($p->id, 0)) - ($actuals->get($p->id, ['total'=>0])['total']), 0),
            'no_pay'      => $actuals->get($p->id, ['no_pay'=>0])['no_pay'],
            'carried'     => $actuals->get($p->id, ['carried'=>false])['carried'],
        ]);

        return [$rows];
    }

    // ════════════════════════════════════════════════════════════════════
    // PDF Exports
    // ════════════════════════════════════════════════════════════════════
    //
    // Each method rebuilds the same data used by its HTML counterpart and
    // renders it through resources/views/pdf/*.blade.php via PdfExportService.
    // All exports are audit-logged (export_audit_logs) with the requesting
    // user, IP address, and user agent for compliance traceability.

    public function exportSnapshotPdf(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        [$rows] = $this->buildSnapshotRows($year, $month);

        $monthLabel = \DateTime::createFromFormat('!m', $month)->format('F');

        return PdfExportService::make(
            view: 'pdf.snapshot',
            data: [
                'rows'           => $rows,
                'reportTitle'    => 'Historical Cadre Snapshot',
                'reportSubtitle' => "As at {$monthLabel} {$year}",
            ],
            filename: "cadre-snapshot-{$year}-{$month}.pdf",
            orientation: 'landscape',
            userId: $request->user()->id,
            exportType: 'snapshot_pdf',
        )->download($request->ip(), $request->userAgent());
    }

    public function exportTrendPdf(Request $request)
    {
        $positionId = $request->query('position_id');
        $year       = (int) $request->query('year', now()->year);

        abort_if(! $positionId, 400, 'A position must be selected before exporting the trend report.');

        $position = Position::findOrFail($positionId);
        $approved = ApprovedCarder::forYear($year)->where('position_id', $positionId)->sum('approved_amount');

        $months = collect(range(1, 12))->map(fn ($m) => [
            'month' => $m, 'label' => \DateTime::createFromFormat('!m', $m)->format('M'),
            'in_position' => null, 'vacancy' => null, 'no_pay_leave' => null,
        ]);

        $entries = CarderMonthlyEntry::where('position_id', $positionId)->where('year', $year)->get()->keyBy('month');

        $months = $months->map(function ($m) use ($entries, $approved) {
            $e = $entries->get($m['month']);
            $inPos = $e ? ($e->males + $e->females + $e->no_pay_leave) : null;
            return [...$m, 'in_position' => $inPos, 'vacancy' => $inPos !== null ? max($approved - $inPos, 0) : null, 'no_pay_leave' => $e?->no_pay_leave];
        });

        $trendData = compact('position', 'months', 'approved', 'year');

        return PdfExportService::make(
            view: 'pdf.trend',
            data: [
                'trendData'      => $trendData,
                'reportTitle'    => '12-Month Trend Report',
                'reportSubtitle' => "{$position->title} — {$year}",
            ],
            filename: "trend-{$position->code}-{$year}.pdf",
            orientation: 'portrait',
            userId: $request->user()->id,
            exportType: 'trend_pdf',
        )->download($request->ip(), $request->userAgent());
    }

    public function exportYoyPdf(Request $request)
    {
        $year1 = (int) $request->query('year1', now()->year - 1);
        $year2 = (int) $request->query('year2', now()->year);
        $month = (int) $request->query('month', now()->month);

        $positions = Position::active()->orderBy('title')->get();

        $act1 = CarderMonthlyEntry::forPeriodWithCarryForward($year1, $month)->groupBy('position_id')->map(fn ($r) => $r->sum('males') + $r->sum('females') + $r->sum('no_pay_leave'));
        $act2 = CarderMonthlyEntry::forPeriodWithCarryForward($year2, $month)->groupBy('position_id')->map(fn ($r) => $r->sum('males') + $r->sum('females') + $r->sum('no_pay_leave'));
        $ap1  = ApprovedCarder::forYear($year1)->get()->groupBy('position_id')->map(fn ($r) => $r->sum('approved_amount'));
        $ap2  = ApprovedCarder::forYear($year2)->get()->groupBy('position_id')->map(fn ($r) => $r->sum('approved_amount'));

        $rows = $positions->map(fn ($p) => (object) [
            'title'         => $p->title,
            'approved1'     => $ap1->get($p->id, 0),
            'in_pos1'       => $act1->get($p->id, 0),
            'approved2'     => $ap2->get($p->id, 0),
            'in_pos2'       => $act2->get($p->id, 0),
            'change_in_pos' => $act2->get($p->id, 0) - $act1->get($p->id, 0),
        ]);

        $monthLabel = \DateTime::createFromFormat('!m', $month)->format('F');

        return PdfExportService::make(
            view: 'pdf.yoy',
            data: [
                'rows'           => $rows,
                'year1'          => $year1,
                'year2'          => $year2,
                'reportTitle'    => 'Year-on-Year Comparison',
                'reportSubtitle' => "{$monthLabel} {$year1} vs {$monthLabel} {$year2}",
            ],
            filename: "yoy-{$year1}-vs-{$year2}.pdf",
            orientation: 'landscape',
            userId: $request->user()->id,
            exportType: 'yoy_pdf',
        )->download($request->ip(), $request->userAgent());
    }

    public function exportRetirementPdf(Request $request)
    {
        $horizon = (int) $request->query('months', 24);

        $employees = Employee::active()
            ->whereNotNull('date_of_birth')
            ->with(['position', 'subjectCode'])
            ->get()
            ->map(function ($e) use ($horizon) {
                $retireAge  = $e->retirement_age ?? 60;
                $dob        = $e->date_of_birth;
                $retireDate = $dob->copy()->addYears($retireAge);
                $monthsLeft = now()->diffInMonths($retireDate, false);
                $ageNow     = now()->diffInYears($dob);
                return (object) [
                    'name' => $e->name, 'pay_no' => $e->pay_no,
                    'position'    => $e->position?->title ?? '—',
                    'dob'         => $dob->format('d M Y'),
                    'age'         => $ageNow,
                    'retire_date' => $retireDate->format('d M Y'),
                    'months_left' => $monthsLeft,
                    'status'      => $monthsLeft < 0 ? 'overdue' : ($monthsLeft <= 6 ? 'critical' : ($monthsLeft <= 12 ? 'soon' : ($monthsLeft <= $horizon ? 'upcoming' : 'future'))),
                ];
            })
            ->filter(fn ($e) => $e->months_left <= $horizon)
            ->sortBy('months_left')
            ->values();

        return PdfExportService::make(
            view: 'pdf.retirement-projections',
            data: [
                'employees'      => $employees,
                'reportTitle'    => 'Retirement Projections',
                'reportSubtitle' => "Next {$horizon} months",
            ],
            filename: "retirement-projections-{$horizon}m.pdf",
            orientation: 'landscape',
            userId: $request->user()->id,
            exportType: 'retirement_pdf',
        )->download($request->ip(), $request->userAgent());
    }
}
