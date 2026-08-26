<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ServiceLetter;
use App\Models\ServiceLetterTemplate;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\ServiceLetterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Service Letters — Subject Officers draft a formal letter for one of
 * THEIR OWN assigned employees; an Administrative Officer (AO) reviews
 * and e-signs to approve, or rejects with a reason.
 *
 * STATUS MACHINE: draft -> pending_approval -> approved | rejected
 *                                  ^________________________|
 *                                  (officer edits a rejected letter and
 *                                   resubmits — see resubmit())
 *
 * DATA SECURITY — ownership double-enforced exactly like Employee Profiles:
 *   1. authorizeOwnEmployee() here checks the drafting officer's EFFECTIVE
 *      subject codes (permanent + acting) against the employee's subject code.
 *   2. Approval actions are separately gated to AO/Super Admin only,
 *      independent of the drafting officer's own permissions.
 */
class ServiceLetterController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = ServiceLetter::active()->with(['employee', 'draftedBy', 'approvedBy']);

        if ($user->isAdministrativeOfficer()) {
            // AO's primary view is their approval queue, but they can also
            // see everything via the status filter below.
            $status = $request->query('status', 'pending_approval');
        } elseif ($user->isSuperAdmin() || $user->isPlanningOfficer() || $user->isAdminGroup()) {
            $status = $request->query('status');
        } else {
            // Subject Officer — only letters for their own effective subject codes.
            $codeIds = $user->effectiveSubjectCodeIds();
            $query->whereHas('employee', fn ($q) => $q->whereIn('subject_code_id', $codeIds));
            $status = $request->query('status');
        }

        if ($status) {
            $query->where('status', $status);
        }

        $letters = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('service-letters.index', compact('letters', 'status'));
    }

    public function create(Request $request)
    {
        $employees = $this->assignableEmployees($request->user());
        $templates = ServiceLetterTemplate::active()->orderBy('name')->get();

        return view('service-letters.form', compact('employees', 'templates'));
    }

    /**
     * Detail / approval page for one letter. Ownership check mirrors
     * index(): the drafting officer, any AO/Planning/Admin/Super Admin can
     * view; a different Subject Officer cannot.
     */
    public function show(Request $request, ServiceLetter $serviceLetter)
    {
        $user = $request->user();
        $serviceLetter->load(['employee.position', 'employee.subjectCode', 'template', 'draftedBy', 'approvedBy', 'eSignature']);

        $canView = $user->isSuperAdmin() || $user->isPlanningOfficer() || $user->isAdminGroup()
            || $serviceLetter->drafted_by === $user->id
            || ($user->isSubjectOfficer() && $user->hasEffectiveSubjectCode($serviceLetter->employee->subject_code_id));

        abort_unless($canView, 403, 'You are not authorised to view this service letter.');

        return view('service-letters.show', compact('serviceLetter'));
    }

    /**
     * AJAX-friendly preview: renders the selected template against the
     * selected employee so the officer can see the letter before saving.
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'template_id' => ['required', 'integer', 'exists:service_letter_templates,id'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $this->authorizeOwnEmployee($request, $employee);

        $template = ServiceLetterTemplate::findOrFail($data['template_id']);
        $rendered = ServiceLetterService::render($template, $employee);

        return response()->json(['rendered_body' => $rendered]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id'   => ['required', 'integer', 'exists:employees,id'],
            'template_id'   => ['nullable', 'integer', 'exists:service_letter_templates,id'],
            'subject'       => ['required', 'string', 'max:200'],
            'rendered_body' => ['required', 'string', 'max:5000'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $this->authorizeOwnEmployee($request, $employee);

        $letter = ServiceLetter::create([
            'employee_id'   => $employee->id,
            'template_id'   => $data['template_id'] ?? null,
            'subject'       => $data['subject'],
            'rendered_body' => $data['rendered_body'],
            'status'        => ServiceLetter::STATUS_DRAFT,
            'drafted_by'    => $request->user()->id,
            'is_active'     => true,
        ]);

        AuditLogService::created($letter, "Drafted service letter \"{$letter->subject}\" for {$employee->display_name}");

        return redirect()->route('service-letters.index')
            ->with('success', 'Service letter drafted. Submit it for AO approval when ready.');
    }

    /**
     * Moves a draft (or a previously-rejected letter, edited and resubmitted)
     * into the AO's approval queue.
     */
    public function submit(Request $request, ServiceLetter $serviceLetter): RedirectResponse
    {
        $this->authorizeOwnEmployee($request, $serviceLetter->employee);
        abort_unless($serviceLetter->isEditable(), 403, 'This letter is no longer editable.');

        $old = $serviceLetter->getOriginal();
        $serviceLetter->update([
            'status'           => ServiceLetter::STATUS_PENDING_APPROVAL,
            'rejection_reason' => null,
        ]);
        AuditLogService::updated($serviceLetter, $old, 'Service letter submitted for AO approval.');

        $aoUsers = \App\Models\User::active()
            ->whereHas('category', fn ($q) => $q->where('name', 'Administrative Officer / Hospital Secretary'))
            ->get();

        NotificationService::sendToMany(
            $aoUsers,
            type:  'service_letter_pending',
            title: 'Service letter awaiting your approval',
            body:  "\"{$serviceLetter->subject}\" for {$serviceLetter->employee->display_name} needs review.",
            link:  route('service-letters.index', ['status' => 'pending_approval']),
        );

        return redirect()->route('service-letters.index')->with('success', 'Submitted for AO approval.');
    }

    /**
     * AO approves — requires the AO's own registered e-signature, which is
     * attached to the letter as proof of who signed and with what image.
     */
    public function approve(Request $request, ServiceLetter $serviceLetter): RedirectResponse
    {
        $this->authorizeApprover($request);
        abort_unless($serviceLetter->status === ServiceLetter::STATUS_PENDING_APPROVAL, 403, 'This letter is not awaiting approval.');

        $signature = $request->user()->eSignature;
        if (! $signature) {
            return redirect()->route('e-signatures.edit')
                ->with('error', 'Please register your e-signature before approving service letters.');
        }

        $request->validate(['confirm_signature' => ['accepted']], [
            'confirm_signature.accepted' => 'You must confirm you are e-signing this approval.',
        ]);

        $old = $serviceLetter->getOriginal();
        $serviceLetter->update([
            'status'         => ServiceLetter::STATUS_APPROVED,
            'approved_by'    => $request->user()->id,
            'approved_at'    => now(),
            'e_signature_id' => $signature->id,
        ]);
        AuditLogService::updated($serviceLetter, $old, "Service letter approved and e-signed by {$request->user()->name}");

        NotificationService::send(
            $serviceLetter->draftedBy,
            type:  'service_letter_approved',
            title: 'Service letter approved',
            body:  "\"{$serviceLetter->subject}\" for {$serviceLetter->employee->display_name} has been approved and signed.",
            link:  route('service-letters.index'),
        );

        return back()->with('success', 'Service letter approved and e-signed.');
    }

    public function reject(Request $request, ServiceLetter $serviceLetter): RedirectResponse
    {
        $this->authorizeApprover($request);
        abort_unless($serviceLetter->status === ServiceLetter::STATUS_PENDING_APPROVAL, 403, 'This letter is not awaiting approval.');

        $request->validate(['rejection_reason' => ['required', 'string', 'min:10', 'max:500']]);

        $old = $serviceLetter->getOriginal();
        $serviceLetter->update([
            'status'           => ServiceLetter::STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);
        AuditLogService::updated($serviceLetter, $old, 'Service letter rejected by AO.');

        NotificationService::send(
            $serviceLetter->draftedBy,
            type:  'service_letter_rejected',
            title: 'Service letter rejected',
            body:  "\"{$serviceLetter->subject}\" for {$serviceLetter->employee->display_name} was rejected: "
                 . \Illuminate\Support\Str::limit($request->input('rejection_reason'), 80),
            link:  route('service-letters.index'),
        );

        return back()->with('success', 'Service letter rejected and sent back to the drafting officer.');
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function assignableEmployees(\App\Models\User $user)
    {
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) {
            return Employee::active()->with(['subjectCode', 'position'])->orderBy('name')->get();
        }

        return Employee::active()
            ->whereIn('subject_code_id', $user->effectiveSubjectCodeIds())
            ->with(['subjectCode', 'position'])
            ->orderBy('name')
            ->get();
    }

    private function authorizeOwnEmployee(Request $request, Employee $employee): void
    {
        $user = $request->user();
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) {
            return;
        }
        if (! $user->isSubjectOfficer() || ! $user->hasEffectiveSubjectCode($employee->subject_code_id)) {
            abort(403, 'You may only issue service letters for employees under your assigned subject codes.');
        }
    }

    private function authorizeApprover(Request $request): void
    {
        abort_unless(
            $request->user()->isAdministrativeOfficer() || $request->user()->isSuperAdmin(),
            403,
            'Only the Administrative Officer (AO) may approve or reject service letters.'
        );
    }
}
