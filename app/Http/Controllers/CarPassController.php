<?php

namespace App\Http\Controllers;

use App\Models\CarPassRequest;
use App\Models\CarPassTemplate;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CarPassAccessService;
use App\Services\FeatureToggleService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CarPassController extends Controller
{
    public function __construct(private readonly CarPassAccessService $access) {}

    public function index(Request $request)
    {
        abort_unless($this->access->canView($request->user()), 403);

        $query = CarPassRequest::query()
            ->with(['employee.position', 'employee.unit', 'template', 'preparer', 'reviewer'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->user()->isSubjectOfficer()
            && ! $request->user()->isSuperAdmin()
            && ! $this->access->isAssignedSubjectOfficer($request->user())) {
            $query->where('prepared_by', $request->user()->id);
        }

        $passes = $query->paginate(25)->withQueryString();
        $assignment = $this->access->currentAssignment();
        $canPrepare = $this->access->isAssignedSubjectOfficer($request->user());
        $canApprove = $this->access->canApprove($request->user());

        return view('car-passes.index', compact('passes', 'assignment', 'canPrepare', 'canApprove'));
    }

    public function create(Request $request)
    {
        $this->authorizePrepare($request);

        $employee = null;
        $templates = collect();

        if ($request->filled('employee_id')) {
            $employee = Employee::with(['position', 'unit'])->findOrFail((int) $request->input('employee_id'));
            abort_unless($employee->is_active, 422, 'Inactive employees cannot receive a new Car Pass.');

            $templates = CarPassTemplate::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->filter(fn (CarPassTemplate $template) => $template->supportsPosition($employee->position_id))
                ->values();
        }

        return view('car-passes.create', compact('employee', 'templates'));
    }

    public function searchEmployee(Request $request)
    {
        $this->authorizePrepare($request);

        $data = $request->validate([
            'search_type' => ['required', Rule::in(['nic', 'pay_no', 'phone'])],
            'search_value' => ['required', 'string', 'max:80'],
        ]);

        $value = trim($data['search_value']);
        $query = Employee::query()->with(['position', 'unit'])->where('is_active', true);

        if ($data['search_type'] === 'nic') {
            $query->wherePiiEquals('nic_number', $value);
        } elseif ($data['search_type'] === 'pay_no') {
            $query->wherePiiEquals('pay_no', $value);
        } else {
            $digits = preg_replace('/\D+/', '', $value);
            abort_if($digits === '', 422, 'Enter a valid phone number.');
            $query->wherePiiEquals('whatsapp_mobile', $digits);
        }

        $matches = $query->limit(5)->get();

        AuditLogService::logExport(
            $request->user()->id,
            'car_pass_exact_employee_lookup',
            'car-pass-search',
            'employee',
            null,
            null,
            $request->ip(),
            $request->userAgent()
        );

        return view('car-passes.search-results', compact('matches', 'data'));
    }

    public function store(Request $request)
    {
        $this->authorizePrepare($request);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'template_id' => ['required', 'integer', 'exists:car_pass_templates,id'],
            'vehicle_registration_no' => ['required', 'string', 'max:40'],
            'vehicle_type' => ['required', Rule::in(['car', 'van', 'motorcycle', 'three_wheeler', 'other'])],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['required', 'date', 'after_or_equal:valid_from'],
            'purpose' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'submit_action' => ['required', Rule::in(['draft', 'submit'])],
        ]);

        $employee = Employee::with(['position', 'unit'])->findOrFail((int) $data['employee_id']);
        abort_unless($employee->is_active, 422, 'Inactive employees cannot receive a Car Pass.');

        $template = CarPassTemplate::findOrFail((int) $data['template_id']);
        abort_unless($template->is_active && $template->supportsPosition($employee->position_id), 422, 'The selected pass format is not active for this employee post.');

        $activeDuplicate = CarPassRequest::query()
            ->where('employee_id', $employee->id)
            ->where('vehicle_registration_no', strtoupper(trim($data['vehicle_registration_no'])))
            ->whereIn('status', [CarPassRequest::STATUS_PENDING, CarPassRequest::STATUS_APPROVED, CarPassRequest::STATUS_ISSUED])
            ->whereDate('valid_to', '>=', today())
            ->exists();

        abort_if($activeDuplicate, 422, 'An active or pending pass already exists for this employee and vehicle registration number.');

        $status = $data['submit_action'] === 'submit'
            ? CarPassRequest::STATUS_PENDING
            : CarPassRequest::STATUS_DRAFT;

        $pass = DB::transaction(function () use ($data, $employee, $template, $request, $status) {
            $pass = CarPassRequest::create([
                'uuid' => (string) Str::uuid(),
                'reference_no' => $this->newReferenceNumber(),
                'employee_id' => $employee->id,
                'template_id' => $template->id,
                'employee_name_snapshot' => $employee->name,
                'pay_no_snapshot' => $employee->pay_no,
                'position_snapshot' => $employee->position?->title,
                'unit_snapshot' => $employee->unit?->name,
                'template_name_snapshot' => $template->name,
                'template_code_snapshot' => $template->code,
                'template_version_snapshot' => $template->version,
                'template_image_snapshot_path' => $template->image_path,
                'vehicle_registration_no' => strtoupper(trim($data['vehicle_registration_no'])),
                'vehicle_type' => $data['vehicle_type'],
                'valid_from' => $data['valid_from'],
                'valid_to' => $data['valid_to'],
                'purpose' => $data['purpose'] ?: null,
                'notes' => $data['notes'] ?: null,
                'status' => $status,
                'prepared_by' => $request->user()->id,
                'submitted_at' => $status === CarPassRequest::STATUS_PENDING ? now() : null,
            ]);

            AuditLogService::created($pass, $status === CarPassRequest::STATUS_PENDING
                ? 'Created and submitted Car Pass request for independent approval.'
                : 'Created Car Pass request as draft.');

            return $pass;
        });

        if ($status === CarPassRequest::STATUS_PENDING) {
            $this->notifyApprovers($pass, $request->user());
        }

        return redirect()->route('car-passes.show', $pass)->with('success', 'Car Pass request saved.');
    }

    public function edit(Request $request, CarPassRequest $carPass)
    {
        $this->authorizePrepare($request);
        abort_unless($carPass->prepared_by === $request->user()->id && $carPass->isMutable(), 403, 'Only your draft/returned request can be edited.');

        $carPass->load(['employee.position', 'employee.unit']);
        $templates = CarPassTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (CarPassTemplate $template) => $template->supportsPosition($carPass->employee->position_id))
            ->values();

        return view('car-passes.edit', compact('carPass', 'templates'));
    }

    public function update(Request $request, CarPassRequest $carPass)
    {
        $this->authorizePrepare($request);
        abort_unless($carPass->prepared_by === $request->user()->id && $carPass->isMutable(), 403);

        $data = $request->validate([
            'template_id' => ['required', 'integer', 'exists:car_pass_templates,id'],
            'vehicle_registration_no' => ['required', 'string', 'max:40'],
            'vehicle_type' => ['required', Rule::in(['car', 'van', 'motorcycle', 'three_wheeler', 'other'])],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['required', 'date', 'after_or_equal:valid_from'],
            'purpose' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $template = CarPassTemplate::findOrFail((int) $data['template_id']);
        abort_unless($template->is_active && $template->supportsPosition($carPass->employee->position_id), 422);

        $old = $carPass->getOriginal();
        $carPass->update([
            'template_id' => $template->id,
            'template_name_snapshot' => $template->name,
            'template_code_snapshot' => $template->code,
            'template_version_snapshot' => $template->version,
            'template_image_snapshot_path' => $template->image_path,
            'vehicle_registration_no' => strtoupper(trim($data['vehicle_registration_no'])),
            'vehicle_type' => $data['vehicle_type'],
            'valid_from' => $data['valid_from'],
            'valid_to' => $data['valid_to'],
            'purpose' => $data['purpose'] ?: null,
            'notes' => $data['notes'] ?: null,
            'status' => CarPassRequest::STATUS_DRAFT,
            'decision_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        AuditLogService::updated($carPass, $old, 'Updated Car Pass draft/returned request.');

        return redirect()->route('car-passes.show', $carPass)->with('success', 'Car Pass request updated.');
    }

    public function show(Request $request, CarPassRequest $carPass)
    {
        abort_unless($this->access->canViewPass($request->user(), $carPass), 403);
        $carPass->load(['employee.position', 'employee.unit', 'template', 'preparer', 'reviewer', 'issuer']);

        $canPrepare = $this->access->isAssignedSubjectOfficer($request->user());
        $canApprove = $this->access->canApprove($request->user()) && (int) $carPass->prepared_by !== (int) $request->user()->id;

        return view('car-passes.show', compact('carPass', 'canPrepare', 'canApprove'));
    }

    public function submit(Request $request, CarPassRequest $carPass)
    {
        $this->authorizePrepare($request);
        abort_unless($carPass->prepared_by === $request->user()->id && $carPass->isMutable(), 403);

        $old = $carPass->getOriginal();
        $carPass->update([
            'status' => CarPassRequest::STATUS_PENDING,
            'submitted_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'decision_note' => null,
        ]);
        AuditLogService::updated($carPass, $old, 'Submitted Car Pass request for independent approval.');
        $this->notifyApprovers($carPass, $request->user());

        return back()->with('success', 'Submitted for approval.');
    }

    public function approve(Request $request, CarPassRequest $carPass)
    {
        $this->authorizeApprove($request, $carPass);
        abort_unless($carPass->status === CarPassRequest::STATUS_PENDING, 422, 'Only pending requests can be approved.');

        $data = $request->validate(['decision_note' => ['nullable', 'string', 'max:1000']]);
        $old = $carPass->getOriginal();
        $hash = hash('sha256', json_encode($this->officialPayload($carPass), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $carPass->update([
            'status' => CarPassRequest::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'approved_at' => now(),
            'decision_note' => $data['decision_note'] ?: null,
            'content_hash' => $hash,
        ]);
        AuditLogService::updated($carPass, $old, 'Approved Car Pass request and sealed the official snapshot hash.');

        return back()->with('success', 'Car Pass approved. It is now locked for issue.');
    }

    public function reject(Request $request, CarPassRequest $carPass)
    {
        $this->authorizeApprove($request, $carPass);
        abort_unless($carPass->status === CarPassRequest::STATUS_PENDING, 422);

        $data = $request->validate(['decision_note' => ['required', 'string', 'min:5', 'max:1000']]);
        $old = $carPass->getOriginal();
        $carPass->update([
            'status' => CarPassRequest::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'decision_note' => $data['decision_note'],
        ]);
        AuditLogService::updated($carPass, $old, 'Returned/rejected Car Pass request with mandatory reason.');

        return back()->with('success', 'Car Pass returned to the responsible Subject Officer.');
    }

    public function issue(Request $request, CarPassRequest $carPass)
    {
        $this->authorizePrepare($request);
        abort_unless($carPass->status === CarPassRequest::STATUS_APPROVED, 422, 'Only approved passes can be issued.');
        abort_unless($carPass->valid_to && $carPass->valid_to->greaterThanOrEqualTo(today()), 422, 'This approved pass has already passed its validity end date and cannot be issued.');

        $currentHash = hash('sha256', json_encode($this->officialPayload($carPass), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        abort_unless(hash_equals((string) $carPass->content_hash, $currentHash), 409, 'The approved snapshot no longer matches its integrity seal.');

        $token = Str::random(64);
        $old = $carPass->getOriginal();
        $carPass->update([
            'status' => CarPassRequest::STATUS_ISSUED,
            'issued_at' => now(),
            'issued_by' => $request->user()->id,
            'verification_token_hash' => hash('sha256', $token),
        ]);
        AuditLogService::updated($carPass, $old, 'Issued approved Car Pass.');

        return back()->with('success', 'Car Pass issued. The approved record remains immutable.');
    }

    public function revoke(Request $request, CarPassRequest $carPass)
    {
        abort_unless($request->user()->isSuperAdmin() || $this->access->canApprove($request->user()), 403);
        abort_unless($carPass->status === CarPassRequest::STATUS_ISSUED, 422);

        $data = $request->validate(['revocation_reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $old = $carPass->getOriginal();
        $carPass->update([
            'status' => CarPassRequest::STATUS_REVOKED,
            'revoked_at' => now(),
            'revoked_by' => $request->user()->id,
            'revocation_reason' => $data['revocation_reason'],
        ]);
        AuditLogService::updated($carPass, $old, 'Revoked issued Car Pass without deleting the historical record.');

        return back()->with('success', 'Car Pass revoked and retained in history.');
    }

    public function print(Request $request, CarPassRequest $carPass)
    {
        abort_unless($this->access->canViewPass($request->user(), $carPass), 403);
        abort_unless(in_array($carPass->status, [CarPassRequest::STATUS_APPROVED, CarPassRequest::STATUS_ISSUED, CarPassRequest::STATUS_REVOKED], true), 403);

        $carPass->load(['employee.position', 'employee.unit', 'reviewer', 'issuer']);
        AuditLogService::logExport(
            $request->user()->id,
            'car_pass_print',
            $carPass->reference_no,
            CarPassRequest::class,
            $carPass->id,
            null,
            $request->ip(),
            $request->userAgent()
        );

        return view('car-passes.print', compact('carPass'));
    }

    public function templateSnapshotImage(Request $request, CarPassRequest $carPass)
    {
        abort_unless($this->access->canViewPass($request->user(), $carPass), 403);
        abort_unless(Storage::disk('local')->exists($carPass->template_image_snapshot_path), 404);

        return response()->file(Storage::disk('local')->path($carPass->template_image_snapshot_path));
    }

    private function authorizePrepare(Request $request): void
    {
        abort_unless(FeatureToggleService::enabled('car_passes'), 404);
        abort_unless($this->access->isAssignedSubjectOfficer($request->user()), 403, 'Only the formally assigned Car Pass Subject Officer has operational write access.');
    }

    private function authorizeApprove(Request $request, CarPassRequest $carPass): void
    {
        abort_unless($this->access->canApprove($request->user()), 403, 'You are not the configured approving authority for Car Passes.');
        abort_if((int) $carPass->prepared_by === (int) $request->user()->id, 403, 'The preparer cannot approve the same Car Pass request.');
    }

    private function newReferenceNumber(): string
    {
        do {
            $reference = 'CP/'.now()->format('Y').'/'.strtoupper(Str::random(6));
        } while (CarPassRequest::where('reference_no', $reference)->exists());

        return $reference;
    }

    private function officialPayload(CarPassRequest $pass): array
    {
        return [
            'reference_no' => $pass->reference_no,
            'employee_id' => $pass->employee_id,
            'employee_name' => $pass->employee_name_snapshot,
            'pay_no' => $pass->pay_no_snapshot,
            'position' => $pass->position_snapshot,
            'unit' => $pass->unit_snapshot,
            'template' => [$pass->template_code_snapshot, $pass->template_version_snapshot],
            'vehicle_registration_no' => $pass->vehicle_registration_no,
            'vehicle_type' => $pass->vehicle_type,
            'valid_from' => optional($pass->valid_from)->toDateString(),
            'valid_to' => optional($pass->valid_to)->toDateString(),
            'purpose' => $pass->purpose,
        ];
    }
    private function notifyApprovers(CarPassRequest $carPass, User $submitter): void
    {
        $role = $this->access->approvalRole();
        $approvers = User::active()
            ->havingRole($role)
            ->where('id', '!=', $submitter->id)
            ->get();

        NotificationService::sendToMany(
            $approvers,
            type: 'car_pass_approval_request',
            title: 'New Request for Approval',
            body: 'A Car Pass request has been submitted and requires independent approval.',
            link: route('car-passes.show', $carPass),
            data: ['menu_route' => 'car-passes.index', 'workflow' => 'car_pass']
        );
    }

}
