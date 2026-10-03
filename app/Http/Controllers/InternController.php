<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Intern;
use App\Models\InternBatch;
use App\Services\AuditLogService;
use App\Services\InternAllocationAccessService;
use App\Services\InternListImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InternController extends Controller
{
    public function index(Request $request, InternBatch $batch)
    {
        InternAllocationAccessService::assertCanView($request->user());

        $interns = Intern::query()
            ->where('intern_batch_id', $batch->id)
            ->where('is_active', true)
            ->with(['assignments.rotationUnit'])
            ->orderBy('id')
            ->get()
            ->sortBy(fn ($intern) => mb_strtolower((string) $intern->name, 'UTF-8'))
            ->values();

        $canEdit = InternAllocationAccessService::canEditBatch($request->user(), $batch);

        return view('intern-batches.interns', compact('batch', 'interns', 'canEdit'));
    }

    public function upload(Request $request, InternBatch $batch): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternAllocationAccessService::assertCanEdit($request->user(), $batch);

        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'max:5120',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/plain,text/csv,application/csv,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'extensions:xlsx,xls,csv,docx',
            ],
        ]);

        $file = $data['file'];
        $extension = $file->getClientOriginalExtension();
        $stored = $file->store('intern-imports', 'local');
        $fullPath = storage_path('app/'.$stored);

        try {
            $result = InternListImportService::import($fullPath, $extension, $batch);
        } catch (\Throwable $exception) {
            @unlink($fullPath);

            return back()->with('error', 'Import failed: '.$exception->getMessage());
        }

        @unlink($fullPath);

        AuditLogService::event(
            $batch,
            'intern_import',
            "Imported {$result['imported']} intern(s) into batch {$batch->name}.",
        );

        $message = "Imported {$result['imported']} intern(s).";
        if ($result['skipped_blank'] > 0) {
            $message .= " {$result['skipped_blank']} blank row(s) skipped.";
        }

        return redirect()->route('intern-batches.interns.index', $batch)->with('success', $message);
    }

    public function updateNic(Request $request, InternBatch $batch, Intern $intern): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternAllocationAccessService::assertCanEdit($request->user(), $batch);
        abort_unless($intern->intern_batch_id === $batch->id && $intern->is_active, 404);

        $data = $request->validate([
            'nic_number' => ['nullable', 'string', 'max:12', 'regex:/^\d{9}[VXvx]$|^\d{12}$/'],
        ], [
            'nic_number.regex' => 'NIC must be 9 digits + V/X (old format) or 12 digits (new format).',
        ]);

        $old = $intern->getOriginal();
        $intern->update([
            'nic_number' => $data['nic_number'] ? strtoupper($data['nic_number']) : null,
        ]);

        AuditLogService::updated($intern, $old, 'Updated intern identity reference.');

        return back()->with('success', "NIC updated for {$intern->name}.");
    }

    public function disable(Request $request, InternBatch $batch, Intern $intern): RedirectResponse
    {
        InternAllocationAccessService::assertCanView($request->user());
        InternAllocationAccessService::assertCanEdit($request->user(), $batch);
        abort_unless($intern->intern_batch_id === $batch->id && $intern->is_active, 404);

        $data = $request->validate([
            'disable_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $old = $intern->getOriginal();
        $intern->update([
            'is_active' => false,
            'disabled_by' => $request->user()->id,
            'disabled_at' => now(),
            'disable_reason' => $data['disable_reason'],
        ]);

        AuditLogService::updated(
            $intern,
            $old,
            "Intern record disabled without deletion. Reason: {$data['disable_reason']}",
        );

        return redirect()
            ->route('intern-batches.interns.index', $batch)
            ->with('success', "{$intern->name} was disabled. Historical assignments remain preserved.");
    }
}
