<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InternBatch;
use App\Models\InternRotationUnit;
use App\Models\InternUnitAllocation;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\InternAllocationAccessService;
use App\Services\InternAllocationAnalysisService;
use App\Services\InternBatchLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InternBatchController extends Controller
{
    public function index(Request $request)
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternBatchLifecycleService::autoCloseEndedBatches();

        $batches = InternBatch::query()
            ->with(['assignedSubjectOfficer:id,name,email'])
            ->withCount([
                'interns',
                'assignments',
                'rhoPlacements',
                'rhoPlacements as rho_placed_count' => fn ($query) => $query->where('status', 'placed'),
            ])
            ->orderByRaw('CASE WHEN end_date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('end_date')
            ->orderByDesc('created_at')
            ->get();

        $canCreate = InternAllocationAccessService::canCreateBatch($request->user());
        $canManageResponsibility = InternAllocationAccessService::canManageResponsibility($request->user());
        $subjectOfficers = $canManageResponsibility ? $this->subjectOfficers() : collect();

        $timelineBatches = $batches
            ->filter(fn (InternBatch $batch) => $batch->start_date || $batch->end_date)
            ->values();

        $myBatches = $request->user()->isSubjectOfficer()
            ? $batches->where('assigned_subject_officer_id', $request->user()->id)->values()
            : collect();

        $timelineStart = Carbon::today()->startOfMonth()->subMonths(6);
        $timelineEnd = $timelineStart->copy()->addMonths(24)->endOfMonth();
        $timelineDays = max(1, $timelineStart->diffInDays($timelineEnd));
        $today = Carbon::today();
        $todayOffset = $today->betweenIncluded($timelineStart, $timelineEnd)
            ? round(($timelineStart->diffInDays($today, false) / $timelineDays) * 100, 2)
            : null;

        $timelineMonths = collect();
        $monthCursor = $timelineStart->copy()->startOfMonth();
        while ($monthCursor->lte($timelineEnd)) {
            $timelineMonths->push([
                'key' => $monthCursor->format('Y-m'),
                'month' => $monthCursor->format('M'),
                'year' => $monthCursor->format('Y'),
                'label' => $monthCursor->format('M Y'),
            ]);
            $monthCursor->addMonth();
        }

        $timelineRows = $timelineBatches->map(function (InternBatch $batch) use ($timelineStart, $timelineEnd, $timelineDays, $today): array {
            $start = $batch->start_date ?: $timelineStart;
            $end = $batch->end_date ?: $start;
            $clampedStart = $start->lt($timelineStart)
                ? $timelineStart->copy()
                : ($start->gt($timelineEnd) ? $timelineEnd->copy() : $start->copy());
            $clampedEnd = $end->gt($timelineEnd)
                ? $timelineEnd->copy()
                : ($end->lt($timelineStart) ? $timelineStart->copy() : $end->copy());
            $left = min(100, max(0, ($timelineStart->diffInDays($clampedStart, false) / $timelineDays) * 100));
            $width = max(0.9, ($clampedStart->diffInDays($clampedEnd) / $timelineDays) * 100);
            $status = $batch->operational_status;
            $rhoPending = max(0, (int) $batch->interns_count - (int) $batch->rho_placed_count);
            $rhoPercent = (int) $batch->interns_count > 0
                ? round(((int) $batch->rho_placed_count / (int) $batch->interns_count) * 100, 1)
                : 0.0;

            if ($batch->is_active && $batch->start_date && $today->lt($batch->start_date)) {
                $milestoneLabel = 'Starts';
                $milestoneDate = $batch->start_date->format('d M Y');
            } elseif ($batch->is_active && $batch->end_date) {
                $milestoneLabel = 'Ends';
                $milestoneDate = $batch->end_date->format('d M Y');
            } else {
                $milestoneLabel = 'Closed';
                $milestoneDate = $batch->closed_at?->format('d M Y') ?? $batch->end_date?->format('d M Y') ?? 'Recorded';
            }

            return [
                'id' => $batch->id,
                'name' => $batch->name,
                'officer' => $batch->assignedSubjectOfficer?->name ?? 'Unassigned',
                'status' => $status,
                'status_label' => match ($status) {
                    'ongoing' => 'Ongoing',
                    'upcoming' => 'Upcoming',
                    'closed' => $batch->closed_automatically ? 'Auto Closed' : 'Closed',
                    'ended' => 'Ended',
                    default => 'Open',
                },
                'left' => round($left, 2),
                'width' => round(min($width, max(0.9, 100 - $left)), 2),
                'period' => ($batch->start_date?->format('d M Y') ?? 'No start').' – '.($batch->end_date?->format('d M Y') ?? 'No end'),
                'interns' => (int) $batch->interns_count,
                'rho_placed' => (int) $batch->rho_placed_count,
                'rho_pending' => $rhoPending,
                'rho_percent' => $rhoPercent,
                'milestone_label' => $milestoneLabel,
                'milestone_date' => $milestoneDate,
                'tooltip' => $batch->name.' | '.($batch->start_date?->format('d M Y') ?? 'No start').' – '.($batch->end_date?->format('d M Y') ?? 'No end').' | '.ucfirst($status),
            ];
        })->values();

        $timelineSummary = [
            'ongoing' => $batches->filter(fn (InternBatch $batch) => $batch->operational_status === 'ongoing')->count(),
            'upcoming' => $batches->filter(fn (InternBatch $batch) => $batch->operational_status === 'upcoming')->count(),
            'active_interns' => $batches->where('is_active', true)->sum('interns_count'),
            'rho_pending' => $batches->where('is_active', false)->sum(function (InternBatch $batch): int {
                return max(0, (int) $batch->interns_count - (int) $batch->rho_placed_count);
            }),
        ];

        return view('intern-batches.index', compact(
            'batches',
            'timelineRows',
            'timelineStart',
            'timelineEnd',
            'timelineMonths',
            'timelineSummary',
            'todayOffset',
            'myBatches',
            'canCreate',
            'canManageResponsibility',
            'subjectOfficers',
        ));
    }

    public function analysis(Request $request)
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternBatchLifecycleService::autoCloseEndedBatches();

        $analysis = InternAllocationAnalysisService::build($request->user());

        return view('intern-batches.analysis', $analysis);
    }

    public function governance(Request $request, InternBatch $batch)
    {
        InternAllocationAccessService::assertCanView($request->user());

        $canManageResponsibility = InternAllocationAccessService::canManageResponsibility($request->user());
        abort_unless(
            $canManageResponsibility || InternAllocationAccessService::ownsBatch($request->user(), $batch),
            403,
            'Only Super Admin or the Subject Officer assigned to this batch can open batch governance controls.',
        );

        $batch->load([
            'assignedSubjectOfficer:id,name,email',
            'responsibilityAssigner:id,name,email',
        ]);

        $subjectOfficers = $canManageResponsibility
            ? $this->subjectOfficers()
            : collect();

        $responsibilityHistory = DB::table('intern_batch_responsibility_history')
            ->leftJoin('users as previous_users', 'previous_users.id', '=', 'intern_batch_responsibility_history.previous_user_id')
            ->leftJoin('users as new_users', 'new_users.id', '=', 'intern_batch_responsibility_history.new_user_id')
            ->leftJoin('users as changed_users', 'changed_users.id', '=', 'intern_batch_responsibility_history.changed_by')
            ->where('intern_batch_responsibility_history.intern_batch_id', $batch->id)
            ->orderByDesc('intern_batch_responsibility_history.id')
            ->select([
                'intern_batch_responsibility_history.*',
                'previous_users.name as previous_user_name',
                'new_users.name as new_user_name',
                'changed_users.name as changed_by_name',
            ])
            ->get();

        return view('intern-batches.governance', compact(
            'batch',
            'canManageResponsibility',
            'subjectOfficers',
            'responsibilityHistory',
        ));
    }

    public function create(Request $request)
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternAllocationAccessService::assertCanCreate($request->user());

        $canAssignOfficer = InternAllocationAccessService::canManageResponsibility($request->user());
        $subjectOfficers = $canAssignOfficer ? $this->subjectOfficers() : collect([$request->user()]);

        return view('intern-batches.create', compact('canAssignOfficer', 'subjectOfficers'));
    }

    public function store(Request $request): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternAllocationAccessService::assertCanCreate($request->user());

        $canAssignOfficer = InternAllocationAccessService::canManageResponsibility($request->user());

        $rules = [
            'name' => ['required', 'string', 'max:100', 'unique:intern_batches,name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];

        if ($canAssignOfficer) {
            $rules['assigned_subject_officer_id'] = [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ];
        }

        $data = $request->validate($rules);
        $assignedOfficerId = $canAssignOfficer
            ? (int) $data['assigned_subject_officer_id']
            : (int) $request->user()->id;

        $assignedOfficer = User::findOrFail($assignedOfficerId);
        abort_unless(
            $assignedOfficer->is_active && $assignedOfficer->isSubjectOfficer(),
            422,
            'The responsible officer must be an active Subject Officer.',
        );

        $batch = InternBatch::create([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'assigned_subject_officer_id' => $assignedOfficerId,
            'responsibility_assigned_by' => $request->user()->id,
            'responsibility_assigned_at' => now(),
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        foreach (InternRotationUnit::active()->get() as $unit) {
            foreach ([1, 2] as $appointmentNumber) {
                InternUnitAllocation::firstOrCreate(
                    [
                        'intern_batch_id' => $batch->id,
                        'intern_rotation_unit_id' => $unit->id,
                        'appointment_number' => $appointmentNumber,
                    ],
                    ['capacity' => 0],
                );
            }
        }

        AuditLogService::created(
            $batch,
            "Created Intern Medical Officer batch {$batch->name} and assigned operational responsibility to {$assignedOfficer->name}.",
        );

        if (InternAllocationAccessService::canEditBatch($request->user(), $batch)) {
            return redirect()
                ->route('intern-batches.allocations.edit', $batch)
                ->with('success', "Batch \"{$batch->name}\" created. Set the rotation capacity next.");
        }

        return redirect()
            ->route('intern-batches.index')
            ->with('success', "Batch \"{$batch->name}\" created and assigned to {$assignedOfficer->name}. Operational screens are read-only for oversight roles.");
    }

    public function updatePeriod(Request $request, InternBatch $batch): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        abort_unless(
            InternAllocationAccessService::ownsBatch($request->user(), $batch),
            403,
            'Only the Subject Officer assigned to this batch can update its official start and end dates.',
        );

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $old = $batch->getOriginal();
        $batch->update([
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        AuditLogService::updated(
            $batch,
            $old,
            "Updated official intern batch period. Reason: {$data['reason']}",
        );

        return back()->with('success', 'Batch start and end dates updated and audit logged.');
    }

    public function updateResponsibility(Request $request, InternBatch $batch): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        abort_unless(
            InternAllocationAccessService::canManageResponsibility($request->user()),
            403,
            'Only Super Admin can formally assign or reassign batch responsibility.',
        );

        $data = $request->validate([
            'assigned_subject_officer_id' => ['required', 'integer', 'exists:users,id'],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $officer = User::findOrFail((int) $data['assigned_subject_officer_id']);
        abort_unless(
            $officer->is_active && $officer->isSubjectOfficer(),
            422,
            'Select an active Subject Officer.',
        );

        $old = $batch->getOriginal();
        $previousOfficerId = $batch->assigned_subject_officer_id;
        $previousOfficer = $batch->assignedSubjectOfficer?->name ?? 'Unassigned';

        DB::transaction(function () use ($batch, $data, $officer, $request, $previousOfficerId): void {
            $updates = [
                'assigned_subject_officer_id' => $officer->id,
                'responsibility_assigned_by' => $request->user()->id,
                'responsibility_assigned_at' => now(),
            ];

            if (! empty($data['start_date'])) {
                $updates['start_date'] = $data['start_date'];
            }

            if (! empty($data['end_date'])) {
                $updates['end_date'] = $data['end_date'];
            }

            $batch->update($updates);

            DB::table('intern_batch_responsibility_history')->insert([
                'intern_batch_id' => $batch->id,
                'previous_user_id' => $previousOfficerId,
                'new_user_id' => $officer->id,
                'effective_date' => $data['effective_date'],
                'reason' => $data['reason'],
                'reference_no' => $data['reference_no'] ?? null,
                'changed_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        AuditLogService::updated(
            $batch,
            $old,
            "Intern batch responsibility changed from {$previousOfficer} to {$officer->name}. Effective {$data['effective_date']}. Reason: {$data['reason']}",
        );

        $batch->refresh()->load('assignedSubjectOfficer:id,name,email');

        return redirect()
            ->route('intern-batches.index')
            ->with(
                'success',
                "Responsibility assigned to {$officer->name}. All other Subject Officers remain read-only for this batch.",
            );
    }

    public function toggleActive(Request $request, InternBatch $batch): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        abort_unless(
            InternAllocationAccessService::ownsBatch($request->user(), $batch),
            403,
            'Only the Subject Officer assigned to this batch can close or reopen it.',
        );

        $old = $batch->getOriginal();
        $reopening = ! $batch->is_active;

        if ($reopening) {
            abort_if(
                $batch->end_date !== null && Carbon::today()->gt($batch->end_date),
                422,
                'An ended batch cannot be reopened for operational allocation. Use the RHO placement follow-up workflow for post-batch activity.',
            );

            $batch->update([
                'is_active' => true,
                'closed_at' => null,
                'closed_by' => null,
                'close_reason' => null,
                'closed_automatically' => false,
            ]);
        } else {
            $batch->update([
                'is_active' => false,
                'closed_at' => now(),
                'closed_by' => $request->user()->id,
                'close_reason' => 'Manually closed by the responsible Subject Officer.',
                'closed_automatically' => false,
            ]);
        }

        AuditLogService::updated(
            $batch,
            $old,
            $batch->is_active ? 'Reopened intern batch.' : 'Closed intern batch.',
        );

        return redirect()
            ->route('intern-batches.index')
            ->with('success', "\"{$batch->name}\" ".($batch->is_active ? 'reopened' : 'closed').'.');
    }

    private function subjectOfficers()
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('userRoles', fn ($query) => $query->where('role', User::ROLE_SUBJECT_OFFICER))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
