<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeIncrement;
use App\Models\EmployeeInterdiction;
use App\Models\EmployeePromotion;
use App\Models\EmployeeTrainingRecord;
use App\Models\RetirementProject;
use App\Models\TransferRecord;
use App\Services\WorkforceScopeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CareerEventTimelineController extends Controller
{
    private const EVENT_TYPES = [
        'promotion',
        'retirement',
        'increment',
        'training_expiry',
        'registration_expiry',
        'transfer',
        'interdiction',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->isSuperAdmin()
                || $user->isPlanningOfficer()
                || $user->isAdminGroup()
                || $user->isSubjectOfficer(),
            403,
            'You do not have access to the Career Events timeline.'
        );

        $start = $this->resolveStartDate($request);
        $end = $start->copy()->addMonths(12)->endOfMonth();
        $selectedTypes = $this->resolveEventTypes($request);
        $identityRestricted = $user->isAdminGroup() && ! $user->isSuperAdmin();

        $employeeQuery = $identityRestricted
            ? Employee::query()
            : WorkforceScopeService::employeeQuery($user);

        $employeeQuery->where('is_active', true)
            ->with(['position', 'unit', 'subjectCode'])
            ->orderBy('id');

        $search = trim((string) $request->query('q', ''));

        if ($search !== '' && ! $identityRestricted) {
            $employeeQuery->where(function ($query) use ($search) {
                $query->wherePiiEquals('name', $search)
                    ->orWhere(fn ($inner) => $inner->wherePiiEquals('nic_number', $search))
                    ->orWhereHas('unit', function ($unitQuery) use ($search) {
                        $unitQuery->whereRaw('LOWER(name) = LOWER(?)', [$search]);
                    });
            });
        }

        $employees = $employeeQuery->get();
        $employeeIds = $employees->pluck('id');
        $events = $this->loadEvents($employeeIds, $start, $end, $selectedTypes);

        $rows = $identityRestricted
            ? $this->buildAggregateRows($employees, $events)
            : $this->buildEmployeeRows($employees, $events);

        $timelineDays = max(1, $start->diffInDays($end));
        $todayOffset = null;

        if (today()->between($start, $end)) {
            $todayOffset = max(
                0,
                min(
                    100,
                    ($start->diffInDays(today(), false) / $timelineDays) * 100
                )
            );
        }

        $rows = $this->prepareTimelineRows(
            $rows,
            $start,
            $timelineDays
        );

        $months = collect(range(0, 12))->map(
            fn (int $offset) => $start->copy()->addMonths($offset)->startOfMonth()
        );

        $summary = [
            'employees' => $rows->count(),
            'events' => $rows->sum(fn (array $row) => $row['events']->count()),
            'high_priority' => $rows->where('risk', 'high')->count(),
            'due_90' => $rows->filter(function (array $row) {
                return Carbon::parse($row['next_event']['date'])->between(today(), today()->copy()->addDays(90));
            })->count(),
        ];

        return view('workforce.career-events-timeline', [
            'rows' => $rows,
            'months' => $months,
            'start' => $start,
            'end' => $end,
            'selectedTypes' => $selectedTypes,
            'summary' => $summary,
            'identityRestricted' => $identityRestricted,
            'search' => $search,
            'todayOffset' => $todayOffset,
        ]);
    }

    private function prepareTimelineRows(
        Collection $rows,
        Carbon $timelineStart,
        int $timelineDays
    ): Collection {
        return $rows->map(function (array $row) use ($timelineStart, $timelineDays) {
            $identityRestricted = (bool) $row['identity_restricted'];

            $row['identity_subtitle'] = $identityRestricted
                ? ((isset($row['group_size']) ? $row['group_size'] : 0).' employee records in group')
                : ($row['display_identifier']
                    ? 'NIC: '.$row['display_identifier']
                    : 'Identity protected for this role');

            $row['next_event_display_date'] = $identityRestricted
                ? Carbon::parse($row['next_event']['date'])->format('M Y')
                : Carbon::parse($row['next_event']['date'])->format('d M Y');

            $row['events'] = collect($row['events'])
                ->map(function (array $event) use ($timelineStart, $timelineDays, $identityRestricted) {
                    $eventDate = Carbon::parse($event['date']);
                    $eventCount = isset($event['count']) ? (int) $event['count'] : null;
                    $eventCountTitle = $eventCount !== null
                        ? ' — '.$eventCount.' recorded events'
                        : '';
                    $eventDisplayDate = $identityRestricted
                        ? $eventDate->format('M Y')
                        : $eventDate->format('d M Y');

                    $event['timeline_offset'] = max(
                        0,
                        min(
                            99.2,
                            ($timelineStart->diffInDays($eventDate, false) / $timelineDays) * 100
                        )
                    );
                    $event['count_badge'] = $eventCount !== null
                        ? ' ×'.$eventCount
                        : '';
                    $event['display_date'] = $eventDisplayDate;
                    $event['short_date'] = $identityRestricted
                        ? $eventDate->format('M Y')
                        : $eventDate->format('d M');
                    $event['tooltip'] = $event['label']
                        .$eventCountTitle
                        .' — '.$eventDisplayDate
                        .' — '.$event['source'];
                    $event['aria_label'] = $event['label']
                        .($identityRestricted ? ' during ' : ' on ')
                        .$eventDisplayDate;

                    return $event;
                })
                ->values();

            return $row;
        });
    }

    private function loadEvents(
        Collection $employeeIds,
        Carbon $start,
        Carbon $end,
        array $selectedTypes
    ): Collection {
        $events = collect();

        foreach ($employeeIds as $employeeId) {
            $events->put($employeeId, collect());
        }

        if (in_array('promotion', $selectedTypes, true)) {
            EmployeePromotion::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('effective_date', [$start->toDateString(), $end->toDateString()])
                ->with('toGrade')
                ->get()
                ->each(function (EmployeePromotion $promotion) use ($events) {
                    $events->get($promotion->employee_id)?->push([
                        'type' => 'promotion',
                        'label' => 'Grade Promotion',
                        'short' => 'PROM',
                        'date' => $promotion->effective_date,
                        'status' => $promotion->status,
                        'detail' => $promotion->toGrade?->name,
                        'source' => 'Approved promotion workflow',
                    ]);
                });
        }

        if (in_array('retirement', $selectedTypes, true)) {
            RetirementProject::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('retirement_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->each(function (RetirementProject $project) use ($events) {
                    $events->get($project->employee_id)?->push([
                        'type' => 'retirement',
                        'label' => 'Retirement',
                        'short' => 'RET',
                        'date' => $project->retirement_date,
                        'status' => $project->status,
                        'detail' => $project->reference_no,
                        'source' => 'Retirement project',
                    ]);
                });
        }

        if (in_array('increment', $selectedTypes, true)) {
            EmployeeIncrement::query()
                ->active()
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('increment_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->each(function (EmployeeIncrement $increment) use ($events) {
                    $events->get($increment->employee_id)?->push([
                        'type' => 'increment',
                        'label' => 'Increment',
                        'short' => 'INC',
                        'date' => $increment->increment_date,
                        'status' => $increment->workflow_status,
                        'detail' => $increment->reference_no,
                        'source' => 'Increment record',
                    ]);
                });
        }

        if (in_array('training_expiry', $selectedTypes, true)) {
            EmployeeTrainingRecord::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereNotNull('expires_on')
                ->whereBetween('expires_on', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->each(function (EmployeeTrainingRecord $record) use ($events) {
                    $events->get($record->employee_id)?->push([
                        'type' => 'training_expiry',
                        'label' => 'Training Expiry',
                        'short' => 'TRN',
                        'date' => $record->expires_on,
                        'status' => $record->status,
                        'detail' => $record->course_name,
                        'source' => 'Training record',
                    ]);
                });
        }

        if (in_array('registration_expiry', $selectedTypes, true)) {
            Employee::query()
                ->whereIn('id', $employeeIds)
                ->whereNotNull('professional_registration_expiry')
                ->whereBetween('professional_registration_expiry', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->each(function (Employee $employee) use ($events) {
                    $events->get($employee->id)?->push([
                        'type' => 'registration_expiry',
                        'label' => 'Registration Expiry',
                        'short' => 'REG',
                        'date' => $employee->professional_registration_expiry,
                        'status' => 'scheduled',
                        'detail' => null,
                        'source' => 'Employee professional registration',
                    ]);
                });
        }

        if (in_array('transfer', $selectedTypes, true)) {
            TransferRecord::query()
                ->active()
                ->whereNotNull('employee_id')
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('effective_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->each(function (TransferRecord $record) use ($events) {
                    $events->get($record->employee_id)?->push([
                        'type' => 'transfer',
                        'label' => 'Transfer',
                        'short' => 'TRF',
                        'date' => $record->effective_date,
                        'status' => $record->direction === 'out' ? 'outgoing' : 'incoming',
                        'detail' => $record->direction === 'out' ? $record->to_location : $record->from_location,
                        'source' => 'Transfer record',
                    ]);
                });
        }

        if (in_array('interdiction', $selectedTypes, true)) {
            EmployeeInterdiction::query()
                ->active()
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('interdiction_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->each(function (EmployeeInterdiction $record) use ($events) {
                    $events->get($record->employee_id)?->push([
                        'type' => 'interdiction',
                        'label' => 'Interdiction / Review',
                        'short' => 'HOLD',
                        'date' => $record->interdiction_date,
                        'status' => $record->inquiry_status,
                        'detail' => $record->inquiry_reference_no,
                        'source' => 'Interdiction record',
                    ]);
                });
        }

        return $events;
    }

    private function resolveStartDate(Request $request): Carbon
    {
        $month = $request->query('start');

        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        return today()->startOfMonth();
    }

    private function resolveEventTypes(Request $request): array
    {
        $types = $request->query('types', self::EVENT_TYPES);
        $types = is_array($types) ? $types : explode(',', (string) $types);
        $types = array_values(array_intersect(self::EVENT_TYPES, $types));

        return $types === [] ? self::EVENT_TYPES : $types;
    }

    private function riskFor(array $event): string
    {
        $date = Carbon::parse($event['date']);

        if ($date->isPast() || $date->diffInDays(today()) <= 30) {
            return 'high';
        }

        if ($date->diffInDays(today()) <= 90) {
            return 'medium';
        }

        return 'low';
    }

    private function buildEmployeeRows(Collection $employees, Collection $events): Collection
    {
        return $employees
            ->map(function (Employee $employee) use ($events) {
                $employeeEvents = $events->get($employee->id, collect())
                    ->sortBy('date')
                    ->values();

                if ($employeeEvents->isEmpty()) {
                    return null;
                }

                $next = $employeeEvents->first();

                return [
                    'employee' => $employee,
                    'identity_restricted' => false,
                    'display_name' => $employee->name,
                    'display_identifier' => $employee->nic_number,
                    'grade' => $employee->position?->title ?? '—',
                    'unit' => $employee->unit?->name ?? '—',
                    'next_event' => $next,
                    'events' => $employeeEvents,
                    'risk' => $this->riskFor($next),
                ];
            })
            ->filter()
            ->values();
    }

    private function buildAggregateRows(Collection $employees, Collection $events): Collection
    {
        return $employees
            ->groupBy(function (Employee $employee) {
                return ($employee->unit_id ?? 0).':'.($employee->position_id ?? 0);
            })
            ->map(function (Collection $group) use ($events) {
                $first = $group->first();
                $aggregateEvents = collect();

                $group->each(function (Employee $employee) use ($events, $aggregateEvents) {
                    $events->get($employee->id, collect())->each(function (array $event) use ($aggregateEvents) {
                        $month = Carbon::parse($event['date'])->startOfMonth();
                        $key = $event['type'].':'.$month->format('Y-m');

                        if (! $aggregateEvents->has($key)) {
                            $aggregateEvents->put($key, [
                                'type' => $event['type'],
                                'label' => $event['label'],
                                'short' => $event['short'],
                                'date' => $month,
                                'status' => 'aggregate',
                                'detail' => null,
                                'source' => 'Aggregate recorded HR events',
                                'count' => 0,
                            ]);
                        }

                        $item = $aggregateEvents->get($key);
                        $item['count']++;
                        $aggregateEvents->put($key, $item);
                    });
                });

                $aggregateEvents = $aggregateEvents->values()->sortBy('date')->values();

                if ($aggregateEvents->isEmpty()) {
                    return null;
                }

                $next = $aggregateEvents->first();
                $next['label'] = $next['label'].' ('.$next['count'].')';

                return [
                    'employee' => null,
                    'identity_restricted' => true,
                    'display_name' => 'Aggregate workforce group',
                    'display_identifier' => null,
                    'grade' => $first->position?->title ?? 'Unspecified position',
                    'unit' => $first->unit?->name ?? 'Unspecified unit',
                    'next_event' => $next,
                    'events' => $aggregateEvents,
                    'risk' => $this->riskFor($next),
                    'group_size' => $group->count(),
                ];
            })
            ->filter()
            ->values();
    }
}
