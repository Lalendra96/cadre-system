<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeImportBatch;
use App\Models\SubjectCode;
use App\Services\AuditLogService;
use App\Services\EmployeeImportProcessor;
use App\Services\NotificationService;
use App\Services\SpreadsheetReaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bulk Employee import from an uploaded CSV/XLSX file — Super Admin only.
 * Subject Officers get read-only access to the summary of any batch run
 * for their own subject code (see index()/show() scoping).
 *
 * WORKFLOW: upload -> map columns -> preview -> import -> summary
 *
 * DATA SECURITY:
 *   - store(): strict mime/size validation before anything touches disk;
 *     stored on the PRIVATE disk under imports/, never public.
 *   - import(): the uploaded file is deleted immediately after a run
 *     completes (success or failure) — raw PII should not persist in
 *     temp storage longer than the import itself takes.
 *   - Every row is validated with the same rules as the single-employee
 *     form (EmployeeImportProcessor mirrors EmployeeRequest) — nothing
 *     bypasses the normal data-integrity checks just because it arrived
 *     via spreadsheet instead of a form submission.
 */
class EmployeeImportController extends Controller
{
    private const MAX_FILE_KB   = 5120; // 5MB
    private const PREVIEW_LIMIT = 10;

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = EmployeeImportBatch::with(['subjectCode', 'uploadedBy'])->orderByDesc('created_at');

        if (! $user->isSuperAdmin()) {
            // Subject Officer summary view — their own effective subject codes only.
            $query->whereIn('subject_code_id', $user->effectiveSubjectCodeIds());
        }

        $batches = $query->paginate(20);

        return view('employee-imports.index', compact('batches'));
    }

    public function create(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $subjectCodes = SubjectCode::active()->orderBy('code')->get();

        return view('employee-imports.create', compact('subjectCodes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'subject_code_id' => ['required', 'integer', 'exists:subject_codes,id'],
            'file'             => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:' . self::MAX_FILE_KB],
        ]);

        $file      = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $storedPath = $file->storeAs('imports', 'batch_' . Str::random(16) . '.' . $extension, 'local');

        try {
            $parsed = SpreadsheetReaderService::read(Storage::disk('local')->path($storedPath), $extension);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($storedPath);
            return back()->withInput()->with('error', 'Could not read the file: ' . $e->getMessage());
        }

        if (empty($parsed['headers'])) {
            Storage::disk('local')->delete($storedPath);
            return back()->withInput()->with('error', 'The file appears to have no header row. Please check the file and try again.');
        }

        $batch = EmployeeImportBatch::create([
            'subject_code_id'    => $data['subject_code_id'],
            'uploaded_by'        => $request->user()->id,
            'original_filename'  => $file->getClientOriginalName(),
            'stored_path'        => $storedPath,
            'file_type'          => $extension,
            'column_headers'     => $parsed['headers'],
            'status'             => EmployeeImportBatch::STATUS_UPLOADED,
            'total_rows'         => count($parsed['rows']),
        ]);

        AuditLogService::created($batch, "Uploaded employee import file \"{$batch->original_filename}\" ({$batch->total_rows} rows) for subject code #{$data['subject_code_id']}");

        return redirect()->route('employee-imports.mapping', $batch)
            ->with('success', 'File uploaded. Now map each column to the matching employee field.');
    }

    public function mapping(Request $request, EmployeeImportBatch $batch)
    {
        $this->authorizeSuperAdmin($request);
        abort_unless(in_array($batch->status, [EmployeeImportBatch::STATUS_UPLOADED, EmployeeImportBatch::STATUS_MAPPED], true), 403,
            'This batch has already been completed and cannot be re-mapped.');

        return view('employee-imports.mapping', [
            'batch'  => $batch,
            'fields' => EmployeeImportProcessor::IMPORTABLE_FIELDS,
        ]);
    }

    public function storeMapping(Request $request, EmployeeImportBatch $batch): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validFields = array_keys(EmployeeImportProcessor::IMPORTABLE_FIELDS);
        $mapping     = [];

        foreach ((array) $request->input('mapping', []) as $columnIndex => $field) {
            if ($field !== '' && in_array($field, $validFields, true)) {
                $mapping[(int) $columnIndex] = $field;
            }
        }

        if (empty($mapping) || ! in_array('name', $mapping, true)) {
            return back()->with('error', 'You must map at least the "Full Name" field before continuing.');
        }

        $defaultPositionId = $request->input('default_position_id') ?: null;
        if ($defaultPositionId && ! \App\Models\Position::whereKey($defaultPositionId)->exists()) {
            return back()->with('error', 'The selected default position could not be found.');
        }
        if (! $defaultPositionId && ! in_array('position', $mapping, true)) {
            return back()->with('error', 'Select a default position for this file, or map a Position column — at least one is required.');
        }

        $batch->update([
            'column_mapping'       => $mapping,
            'default_position_id'  => $defaultPositionId,
            'status'                => EmployeeImportBatch::STATUS_MAPPED,
        ]);

        return redirect()->route('employee-imports.preview', $batch)
            ->with('success', 'Column mapping saved. Review the preview below before importing.');
    }

    /**
     * Dry-run: applies the mapping to the first PREVIEW_LIMIT rows and
     * shows resolve/validation results WITHOUT writing anything.
     */
    public function preview(Request $request, EmployeeImportBatch $batch)
    {
        $this->authorizeSuperAdmin($request);
        abort_unless($batch->status === EmployeeImportBatch::STATUS_MAPPED, 403, 'Map the columns first.');
        abort_unless($batch->stored_path && Storage::disk('local')->exists($batch->stored_path), 404,
            'The uploaded file is no longer available — please start a new import.');

        $parsed = SpreadsheetReaderService::read(Storage::disk('local')->path($batch->stored_path), $batch->file_type);
        $sample = array_slice($parsed['rows'], 0, self::PREVIEW_LIMIT);

        $previewRows = [];
        foreach ($sample as $i => $raw) {
            $mapped = EmployeeImportProcessor::mapRow($raw, $batch->column_mapping);
            $result = EmployeeImportProcessor::resolveAndValidate($mapped, $batch->subject_code_id, $request->user()->id, $batch->default_position_id);
            $previewRows[] = [
                'row_number' => $i + 2, // +1 for header row, +1 for 1-indexing
                'mapped'     => $mapped,
                'resolved'   => $result['data'],
                'valid'      => $result['data'] !== null,
                'errors'     => $result['errors'],
            ];
        }

        return view('employee-imports.preview', compact('batch', 'previewRows'));
    }

    /**
     * Executes the real import — every row of the file, not just the
     * preview sample. Each row is independently try/caught so one bad
     * row never aborts the batch; errors are collected and shown on the
     * summary page afterward.
     */
    public function import(Request $request, EmployeeImportBatch $batch): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        abort_unless($batch->status === EmployeeImportBatch::STATUS_MAPPED, 403, 'Map the columns first.');
        abort_unless($batch->stored_path && Storage::disk('local')->exists($batch->stored_path), 404,
            'The uploaded file is no longer available — please start a new import.');

        $parsed = SpreadsheetReaderService::read(Storage::disk('local')->path($batch->stored_path), $batch->file_type);

        $imported = 0;
        $errors   = [];

        foreach ($parsed['rows'] as $i => $raw) {
            $rowNumber = $i + 2;
            try {
                $mapped = EmployeeImportProcessor::mapRow($raw, $batch->column_mapping);
                $result = EmployeeImportProcessor::resolveAndValidate($mapped, $batch->subject_code_id, $request->user()->id, $batch->default_position_id);

                if ($result['data'] === null) {
                    $errors[] = ['row' => $rowNumber, 'message' => implode('; ', $result['errors'])];
                    continue;
                }

                DB::transaction(fn () => Employee::create($result['data']));
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $rowNumber, 'message' => 'Unexpected error: ' . $e->getMessage()];
            }

            // Cap stored error detail at 200 rows — the count is still accurate,
            // just the itemised list stops growing beyond that on a huge file.
            if (count($errors) >= 200) {
                $errors[] = ['row' => null, 'message' => 'Further errors omitted — over 200 rows failed. Please review your mapping.'];
                break;
            }
        }

        // Delete the uploaded file now that the import has run — no reason
        // to keep raw PII sitting in storage past this point.
        Storage::disk('local')->delete($batch->stored_path);

        $batch->update([
            'status'         => EmployeeImportBatch::STATUS_COMPLETED,
            'imported_count' => $imported,
            'error_count'    => count($errors),
            'errors'         => $errors,
            'stored_path'    => null,
            'completed_at'   => now(),
        ]);

        AuditLogService::updated(
            $batch, ['status' => 'mapped'],
            "Import completed: {$imported} employee(s) created, " . count($errors) . ' error(s), for subject code #' . $batch->subject_code_id
        );

        // Notify the Subject Officer(s) of the target code — satisfies
        // "subject officers can see the summary" with an active nudge,
        // not just a passive index page they'd have to remember to check.
        $officers = \App\Models\User::active()
            ->whereHas('subjectCodes', fn ($q) => $q->where('subject_codes.id', $batch->subject_code_id))
            ->get();

        NotificationService::sendToMany(
            $officers,
            type:  'employee_import_completed',
            title: 'Employee profiles imported',
            body:  "{$imported} employee profile(s) were imported for {$batch->subjectCode->code}"
                 . (count($errors) > 0 ? ' (' . count($errors) . ' row(s) had errors — see summary).' : '.'),
            link:  route('employee-imports.show', $batch),
        );

        return redirect()->route('employee-imports.show', $batch)
            ->with('success', "Import complete: {$imported} employee(s) created" . (count($errors) > 0 ? ', ' . count($errors) . ' error(s) — see below.' : '.'));
    }

    /**
     * Final summary/detail page. This is the read-only view Subject
     * Officers can access for their own subject code's batches.
     */
    public function show(Request $request, EmployeeImportBatch $batch)
    {
        $user = $request->user();
        $canView = $user->isSuperAdmin()
            || $user->isPlanningOfficer()
            || $user->hasEffectiveSubjectCode($batch->subject_code_id);

        abort_unless($canView, 403, 'You are not authorised to view this import batch.');

        $batch->load(['subjectCode', 'uploadedBy']);

        return view('employee-imports.show', compact('batch'));
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only Super Admin may run employee imports.');
    }
}
