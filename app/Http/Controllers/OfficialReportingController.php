<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OfficialReport;
use App\Services\OfficialReportingService;
use Illuminate\Http\Request;

class OfficialReportingController extends Controller
{
    public function index()
    {
        return view('reports.official', ['reports' => OfficialReport::query()->latest()->paginate(25)]);
    }

    public function prepare(Request $request)
    {
        $data = $request->validate([
            'report_type' => 'required|string|max:80',
            'period_key' => 'required|string|max:30',
            'payload' => 'required|array',
        ]);
        OfficialReportingService::prepare(
            $data['report_type'],
            $data['period_key'],
            $data['payload'],
            $request->user(),
        );

        return back()->with('success', 'Report prepared and awaiting independent checking.');
    }

    public function advance(Request $request, OfficialReport $report)
    {
        $data = $request->validate(['status' => 'required|in:checked,approved']);
        OfficialReportingService::advance($report, $request->user(), $data['status']);

        return back()->with('success', 'Official report workflow updated.');
    }

    public function sign(Request $request, OfficialReport $report)
    {
        OfficialReportingService::sign($report, $request->user());

        return back()->with('success', 'Immutable signed workforce snapshot archived.');
    }
}
