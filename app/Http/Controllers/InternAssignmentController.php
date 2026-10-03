<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Intern;
use App\Models\InternAssignment;
use App\Models\InternBatch;
use App\Models\InternRotationUnit;
use App\Models\InternUnitAllocation;
use App\Services\AuditLogService;
use App\Services\InternAllocationAccessService;
use App\Services\InternAssignmentService;
use App\Services\PdfExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InternAssignmentController extends Controller
{
    public function show(Request $request, InternBatch $batch)
    {
        InternAllocationAccessService::assertCanView($request->user());

        [$rotationUnits, $grid] = $this->buildGrid($batch);
        $unassignedInterns = Intern::query()
            ->where('intern_batch_id', $batch->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereDoesntHave('assignments', fn ($assignmentQuery) => $assignmentQuery->where('appointment_number', 1))
                    ->orWhereDoesntHave('assignments', fn ($assignmentQuery) => $assignmentQuery->where('appointment_number', 2));
            })
            ->orderBy('name')
            ->get();

        $canEdit = InternAllocationAccessService::canEditBatch($request->user(), $batch);

        return view(
            'intern-batches.assignments',
            compact('batch', 'rotationUnits', 'grid', 'unassignedInterns', 'canEdit'),
        );
    }

    public function assign(Request $request, InternBatch $batch): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternAllocationAccessService::assertCanEdit($request->user(), $batch);

        $data = $request->validate([
            'intern_id' => ['required', 'integer', 'exists:interns,id'],
            'intern_rotation_unit_id' => ['required', 'integer', 'exists:intern_rotation_units,id'],
            'appointment_number' => ['required', 'integer', 'in:1,2'],
        ]);

        $intern = Intern::findOrFail($data['intern_id']);
        abort_unless($intern->intern_batch_id === $batch->id && $intern->is_active, 404);
        $rotationUnit = InternRotationUnit::findOrFail($data['intern_rotation_unit_id']);

        try {
            $assignment = InternAssignmentService::assign(
                $intern,
                $rotationUnit,
                (int) $data['appointment_number'],
                $request->user()->id,
            );
            AuditLogService::created($assignment, "Assigned {$intern->name} to {$rotationUnit->name}.");
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $suffix = (int) $data['appointment_number'] === 1 ? 'st' : 'nd';

        return back()->with(
            'success',
            "{$intern->name} assigned to {$rotationUnit->name} ({$data['appointment_number']}{$suffix} Appointment).",
        );
    }

    public function unassign(Request $request, InternBatch $batch, InternAssignment $assignment): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternAllocationAccessService::assertCanEdit($request->user(), $batch);
        abort_unless($assignment->intern_batch_id === $batch->id, 404);

        AuditLogService::event(
            $assignment,
            'unassigned',
            'Intern assignment slot cleared by the assigned Subject Officer.',
        );
        $assignment->delete();

        return back()->with('success', 'Assignment removed. The action has been audit logged.');
    }

    public function exportPdf(Request $request, InternBatch $batch)
    {
        InternAllocationAccessService::assertCanView($request->user());

        [$rotationUnits, $grid] = $this->buildGrid($batch);

        return PdfExportService::make(
            view: 'pdf.intern-allocation',
            data: [
                'batch' => $batch,
                'rotationUnits' => $rotationUnits,
                'grid' => $grid,
                'reportTitle' => 'Allocation of Intern Medical Officers',
                'reportSubtitle' => $batch->name,
            ],
            filename: "intern-allocation-{$batch->name}.pdf",
            orientation: 'portrait',
            userId: $request->user()->id,
            exportType: 'intern_allocation_pdf',
        )->download($request->ip(), $request->userAgent());
    }

    public function exportCsv(Request $request, InternBatch $batch)
    {
        InternAllocationAccessService::assertCanView($request->user());

        $assignments = InternAssignment::query()
            ->where('intern_batch_id', $batch->id)
            ->with(['intern', 'rotationUnit'])
            ->orderBy('appointment_number')
            ->orderBy('intern_rotation_unit_id')
            ->orderBy('slot_number')
            ->get();

        AuditLogService::logExport(
            $request->user()->id,
            'intern_allocation_csv',
            "intern-allocation-{$batch->name}",
            'InternBatch',
            $batch->id,
            null,
            $request->ip(),
            $request->userAgent(),
        );

        $filename = "intern-allocation-{$batch->name}.csv";

        return response()->streamDownload(function () use ($assignments): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Appointment', 'Rotation Unit', 'Slot #', 'Intern Name', 'NIC Number']);

            foreach ($assignments as $assignment) {
                fputcsv($out, [
                    $assignment->appointment_number.($assignment->appointment_number === 1 ? 'st' : 'nd'),
                    $assignment->rotationUnit->name,
                    $assignment->slot_number,
                    $assignment->intern->name,
                    $assignment->intern->nic_number,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function buildGrid(InternBatch $batch): array
    {
        $rotationUnits = InternRotationUnit::active()->ordered()->get();
        $allocations = InternUnitAllocation::where('intern_batch_id', $batch->id)->get();
        $assignments = InternAssignment::where('intern_batch_id', $batch->id)->with('intern')->get();
        $grid = [];

        foreach ($rotationUnits as $unit) {
            foreach ([1, 2] as $appointmentNumber) {
                $capacity = $allocations->first(
                    fn ($allocation) => $allocation->intern_rotation_unit_id === $unit->id
                        && $allocation->appointment_number === $appointmentNumber,
                )?->capacity ?? 0;

                $filled = $assignments
                    ->filter(
                        fn ($assignment) => $assignment->intern_rotation_unit_id === $unit->id
                            && $assignment->appointment_number === $appointmentNumber,
                    )
                    ->keyBy('slot_number');

                $slots = [];
                for ($slot = 1; $slot <= $capacity; $slot++) {
                    $slots[$slot] = $filled->get($slot);
                }

                $grid[$unit->id][$appointmentNumber] = $slots;
            }
        }

        return [$rotationUnits, $grid];
    }
}
