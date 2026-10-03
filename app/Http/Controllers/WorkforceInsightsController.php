<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLifecycleEvent;
use App\Models\TransferRecord;
use App\Services\WorkforceScopeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WorkforceInsightsController extends Controller
{
    public function movements(Request $request)
    {
        $from = Carbon::parse(
            $request->query('from', now()->startOfMonth()->toDateString())
        );
        $to = Carbon::parse(
            $request->query('to', now()->endOfMonth()->toDateString())
        );

        $employeeIds = WorkforceScopeService::employeeQuery($request->user())
            ->pluck('id');

        $events = EmployeeLifecycleEvent::with('employee.position', 'employee.unit')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('effective_date', [$from, $to])
            ->get();

        $transfers = TransferRecord::with('employee.position', 'employee.unit')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('effective_date', [$from, $to])
            ->get();

        $counts = [
            'joined' => $events->whereIn('event_type', ['joined_service', 'active'])->count(),
            'retired' => $events->where('event_type', 'retired')->count(),
            'resigned' => $events->where('event_type', 'resigned')->count(),
            'deceased' => $events->where('event_type', 'deceased')->count(),
            'transfer_in' => $transfers->where('direction', 'in')->count(),
            'transfer_out' => $transfers->where('direction', 'out')->count(),
        ];

        return view(
            'workforce.movements',
            compact('from', 'to', 'events', 'transfers', 'counts')
        );
    }

    public function asAt(Request $request)
    {
        $date = Carbon::parse(
            $request->query('date', now()->toDateString())
        );

        $query = WorkforceScopeService::employeeQuery($request->user())
            ->with(['position', 'unit', 'subjectCode']);

        $query->where(function ($query) use ($date): void {
            $query->whereNull('date_joined_public_service')
                ->orWhereDate('date_joined_public_service', '<=', $date);
        });

        $employees = $query
            ->orderBy('id')
            ->get()
            ->filter(function ($employee) use ($date): bool {
                if (
                    $employee->date_of_birth
                    && $employee->date_of_birth
                        ->copy()
                        ->addYears($employee->retirement_age ?? 60)
                        ->lt($date)
                ) {
                    return false;
                }

                $terminal = $employee->lifecycleEvents()
                    ->whereIn('event_type', ['retired', 'resigned', 'deceased'])
                    ->whereDate('effective_date', '<=', $date)
                    ->exists();

                return ! $terminal;
            });

        return view('workforce.as-at', compact('date', 'employees'));
    }

    public function duplicates(Request $request)
    {
        abort_unless(
            $request->user()->isSuperAdmin() || $request->user()->isPlanningOfficer(),
            403
        );

        $base = WorkforceScopeService::employeeQuery($request->user());

        $nicGroups = (clone $base)
            ->whereNotNull('nic_number_hmac')
            ->selectRaw('nic_number_hmac, count(*) as total')
            ->groupBy('nic_number_hmac')
            ->havingRaw('count(*) > 1')
            ->pluck('nic_number_hmac');

        $payGroups = (clone $base)
            ->whereNotNull('pay_no_hmac')
            ->selectRaw('pay_no_hmac, count(*) as total')
            ->groupBy('pay_no_hmac')
            ->havingRaw('count(*) > 1')
            ->pluck('pay_no_hmac');

        $duplicates = WorkforceScopeService::employeeQuery($request->user())
            ->with(['position', 'unit', 'subjectCode'])
            ->where(function ($query) use ($nicGroups, $payGroups): void {
                if ($nicGroups->isNotEmpty()) {
                    $query->whereIn('nic_number_hmac', $nicGroups);
                }

                if ($payGroups->isNotEmpty()) {
                    $query->orWhereIn('pay_no_hmac', $payGroups);
                }
            })
            ->orderBy('id')
            ->get()
            ->sortBy(fn ($employee) => mb_strtolower((string) $employee->name, 'UTF-8'))
            ->values();

        return view('workforce.duplicates', compact('duplicates'));
    }
}
