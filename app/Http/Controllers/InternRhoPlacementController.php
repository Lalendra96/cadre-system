<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InternBatch;
use App\Models\InternRhoPlacement;
use App\Services\AuditLogService;
use App\Services\InternAllocationAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InternRhoPlacementController extends Controller
{
    public function index(Request $request, InternBatch $batch)
    {
        InternAllocationAccessService::assertCanView($request->user());

        $batch->load([
            'assignedSubjectOfficer:id,name,email',
            'interns' => fn ($query) => $query
                ->with(['assignments.rotationUnit', 'rhoPlacement'])
                ->orderBy('name'),
        ]);

        $canEdit = InternAllocationAccessService::canEditRhoPlacements($request->user(), $batch);
        $placedCount = $batch->interns->filter(
            fn ($intern) => $intern->rhoPlacement?->status === InternRhoPlacement::STATUS_PLACED,
        )->count();

        return view('intern-batches.rho-placements', compact('batch', 'canEdit', 'placedCount'));
    }

    public function update(Request $request, InternBatch $batch): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternAllocationAccessService::assertCanEditRhoPlacements($request->user(), $batch);

        abort_unless(
            $batch->end_date !== null,
            422,
            'Set the batch end date before recording RHO placements.',
        );

        $data = $request->validate([
            'placements' => ['required', 'array'],
            'placements.*.status' => [
                'required',
                Rule::in([
                    InternRhoPlacement::STATUS_PENDING,
                    InternRhoPlacement::STATUS_PLACED,
                    InternRhoPlacement::STATUS_DEFERRED,
                    InternRhoPlacement::STATUS_NOT_PLACED,
                ]),
            ],
            'placements.*.placement_institution' => ['nullable', 'string', 'max:180'],
            'placements.*.placement_unit' => ['nullable', 'string', 'max:180'],
            'placements.*.effective_date' => ['nullable', 'date'],
            'placements.*.reference_no' => ['nullable', 'string', 'max:100'],
            'placements.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $activeInternIds = $batch->interns()->pluck('id')->map(fn ($id) => (int) $id)->all();

        DB::transaction(function () use ($data, $batch, $request, $activeInternIds): void {
            foreach ($data['placements'] as $internId => $placementData) {
                $internId = (int) $internId;
                abort_unless(in_array($internId, $activeInternIds, true), 404);

                if ($placementData['status'] === InternRhoPlacement::STATUS_PLACED) {
                    validator($placementData, [
                        'placement_institution' => ['required', 'string', 'max:180'],
                        'effective_date' => ['required', 'date'],
                        'reference_no' => ['required', 'string', 'max:100'],
                    ])->validate();
                }

                $placement = InternRhoPlacement::firstOrNew([
                    'intern_batch_id' => $batch->id,
                    'intern_id' => $internId,
                ]);

                $old = $placement->exists ? $placement->getOriginal() : [];
                $placement->fill([
                    'status' => $placementData['status'],
                    'placement_institution' => $placementData['placement_institution'] ?? null,
                    'placement_unit' => $placementData['placement_unit'] ?? null,
                    'effective_date' => $placementData['effective_date'] ?? null,
                    'reference_no' => $placementData['reference_no'] ?? null,
                    'notes' => $placementData['notes'] ?? null,
                    'recorded_by' => $placement->recorded_by ?: $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
                $placement->save();

                if ($old) {
                    AuditLogService::updated($placement, $old, 'Updated RHO placement record from the ending intern list.');
                } else {
                    AuditLogService::created($placement, 'Created RHO placement record from the ending intern list.');
                }
            }
        });

        return back()->with('success', 'RHO placement records saved. Each change is retained in the audit trail.');
    }
}
