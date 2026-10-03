<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\AuditLogService;
use App\Services\SecureUploadService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    public function index(Request $r, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($r->user(), $employee);
        $documents = $employee->documents()->with('uploader')->latest('document_date')->latest()->get();

        return view('employee-documents.index', compact('employee', 'documents'));
    }

    public function store(Request $r, Employee $employee)
    {
        WorkforceScopeService::authorizeEmployee($r->user(), $employee);
        $d = $r->validate([
            'category' => 'required|string|max:60',
            'title' => 'required|string|max:180',
            'reference_no' => 'nullable|string|max:100',
            'document_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:document_date',
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
            'is_confidential' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
            'supersedes_id' => 'nullable|integer|exists:employee_documents,id',
        ]);
        if (! empty($d['supersedes_id'])) {
            abort_unless(
                EmployeeDocument::whereKey($d['supersedes_id'])->where('employee_id', $employee->id)->exists(),
                422,
                'The prior version must belong to this employee.',
            );
        }
        $f = $r->file('file');
        SecureUploadService::assertSafe($f);
        $path = $f->store('employee-documents/'.$employee->id, 'local');
        $doc = $employee->documents()->create([
            'category' => $d['category'],
            'title' => $d['title'],
            'reference_no' => $d['reference_no'] ?? null,
            'document_date' => $d['document_date'] ?? null,
            'expiry_date' => $d['expiry_date'] ?? null,
            'file_path' => $path,
            'original_name' => SecureUploadService::safeOriginalName($f),
            'mime_type' => $f->getMimeType(),
            'file_size' => $f->getSize(),
            'is_confidential' => $r->boolean('is_confidential'),
            'uploaded_by' => $r->user()->id,
            'notes' => $d['notes'] ?? null,
            'supersedes_id' => $d['supersedes_id'] ?? null,
            'verification_status' => 'pending',
        ]);
        AuditLogService::created(
            $doc,
            "Uploaded employee document version {$doc->title} for {$employee->display_name}",
        );

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Document version uploaded for verification.');
    }

    public function download(Request $r, Employee $employee, EmployeeDocument $document)
    {
        WorkforceScopeService::authorizeEmployee($r->user(), $employee);
        abort_unless($document->employee_id === $employee->id, 404);
        if ($document->is_confidential) {
            abort_unless(
                $r->user()->isSuperAdmin() || $r->user()->isPlanningOfficer() || $r->user()->isAdminGroup(),
                403,
                'Confidential document.',
            );
        }
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }
}
