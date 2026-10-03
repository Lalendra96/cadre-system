<?php

namespace App\Http\Controllers;

use App\Models\CarPassResponsibilityAssignment;
use App\Models\CarPassTemplate;
use App\Models\Position;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SecureUploadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarPassAdminController extends Controller
{
    public function index()
    {
        $subjectOfficers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('role', User::ROLE_SUBJECT_OFFICER))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $positions = Position::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'code', 'title']);

        $currentAssignment = CarPassResponsibilityAssignment::query()
            ->with('subjectOfficer')
            ->whereNull('ended_at')
            ->latest('effective_from')
            ->latest('id')
            ->first();

        $assignmentHistory = CarPassResponsibilityAssignment::query()
            ->with(['subjectOfficer', 'assignedBy'])
            ->latest('effective_from')
            ->latest('id')
            ->limit(20)
            ->get();

        $templates = CarPassTemplate::query()
            ->with('uploader')
            ->latest()
            ->get();

        $approvalRole = (string) SystemSetting::get('car_pass_approval_role', User::ROLE_ADMIN_GROUP);

        return view('car-passes.admin', compact(
            'subjectOfficers',
            'positions',
            'currentAssignment',
            'assignmentHistory',
            'templates',
            'approvalRole'
        ));
    }

    public function updateGovernance(Request $request)
    {
        $data = $request->validate([
            'subject_officer_id' => ['required', 'integer', 'exists:users,id'],
            'effective_from' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'approval_role' => ['required', 'in:admin_group,planning_officer,super_admin'],
        ]);

        $officer = User::findOrFail((int) $data['subject_officer_id']);
        abort_unless($officer->is_active && $officer->isSubjectOfficer(), 422, 'The selected user is not an active Subject Officer.');

        DB::transaction(function () use ($data, $officer, $request) {
            $current = CarPassResponsibilityAssignment::query()
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($current) {
                $old = $current->getOriginal();
                $current->update([
                    'effective_to' => Carbon::parse($data['effective_from'])->subDay()->toDateString(),
                    'ended_at' => now(),
                    'ended_by' => $request->user()->id,
                    'end_reason' => 'Superseded by a new formal Car Pass responsibility assignment.',
                ]);
                AuditLogService::updated($current, $old, 'Car Pass Subject Officer responsibility ended due to reassignment.');
            }

            $assignment = CarPassResponsibilityAssignment::create([
                'subject_officer_id' => $officer->id,
                'effective_from' => $data['effective_from'],
                'reason' => $data['reason'],
                'reference_no' => $data['reference_no'] ?: null,
                'assigned_by' => $request->user()->id,
            ]);

            AuditLogService::created($assignment, 'Assigned the single responsible Subject Officer for Car Pass operations.');
            SystemSetting::set('car_pass_approval_role', $data['approval_role']);
        });

        return back()->with('success', 'Car Pass responsibility and approval authority updated.');
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'position_ids' => ['required', 'array', 'min:1'],
            'position_ids.*' => ['integer', 'exists:positions,id'],
            'default_validity_months' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $code = strtoupper($data['code']);
        $latestVersion = (int) CarPassTemplate::query()
            ->where('code', $code)
            ->max('version');
        $newVersion = $latestVersion + 1;

        $file = $request->file('image');
        SecureUploadService::assertSafe($file, 'image');
        $storedPath = $file->storeAs(
            'car-pass-templates',
            Str::uuid().'.'.$file->getClientOriginalExtension(),
            'local'
        );

        CarPassTemplate::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'disabled_by' => $request->user()->id,
                'disabled_at' => now(),
                'disable_reason' => 'Superseded by template version '.$newVersion.'.',
                'updated_at' => now(),
            ]);

        $template = CarPassTemplate::create([
            'name' => $data['name'],
            'code' => $code,
            'image_path' => $storedPath,
            'original_filename' => SecureUploadService::safeOriginalName($file),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'allowed_position_ids' => array_values(array_unique(array_map('intval', $data['position_ids']))),
            'default_validity_months' => (int) $data['default_validity_months'],
            'version' => $newVersion,
            'is_active' => true,
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLogService::created($template, 'Uploaded a governed Car Pass template image and linked it to eligible posts.');

        return back()->with('success', 'Car Pass template uploaded.');
    }

    public function toggleTemplate(Request $request, CarPassTemplate $template)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $old = $template->getOriginal();
        $template->update([
            'is_active' => ! $template->is_active,
            'disabled_by' => $template->is_active ? $request->user()->id : null,
            'disabled_at' => $template->is_active ? now() : null,
            'disable_reason' => $template->is_active ? $data['reason'] : null,
        ]);

        AuditLogService::updated($template, $old, $template->is_active
            ? 'Re-enabled Car Pass template.'
            : 'Disabled Car Pass template without deleting historical use.');

        return back()->with('success', 'Template status updated.');
    }

    public function image(CarPassTemplate $template)
    {
        abort_unless(Storage::disk('local')->exists($template->image_path), 404);

        return response()->file(
            Storage::disk('local')->path($template->image_path),
            ['Content-Type' => $template->mime_type]
        );
    }
}
