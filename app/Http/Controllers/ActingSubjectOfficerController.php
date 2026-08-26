<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\ActingSubjectOfficer;
use App\Models\SubjectCode;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Super Admin appoints a user to temporarily act as Subject Officer for
 * a subject code they don't permanently hold — see migration docblock and
 * User::effectiveSubjectCodeIds() for how this affects access.
 *
 * DATA SECURITY: route middleware restricts this whole controller to
 * super_admin, but authorizeSuperAdmin() re-checks independently in every
 * mutating method — the same double-enforcement pattern used throughout
 * this codebase (see ApprovedCarderController::authorizeWrite() for the
 * precedent).
 */
class ActingSubjectOfficerController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $assignments = ActingSubjectOfficer::with(['user', 'subjectCode', 'appointedBy'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('acting-subject-officers.index', compact('assignments'));
    }

    public function create(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $users        = User::active()->havingRole(User::ROLE_SUBJECT_OFFICER)->orderBy('name')->get();
        $subjectCodes = SubjectCode::active()->orderBy('code')->get();

        return view('acting-subject-officers.form', compact('users', 'subjectCodes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'user_id'         => ['required', 'integer', 'exists:users,id'],
            'subject_code_id' => ['required', 'integer', 'exists:subject_codes,id'],
            'start_date'      => ['required', 'date'],
            'end_date'        => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason'          => ['nullable', 'string', 'max:300'],
        ]);

        $data['appointed_by'] = $request->user()->id;
        $data['is_active']    = true;

        $assignment = ActingSubjectOfficer::create($data);
        $assignment->load(['user', 'subjectCode']);

        AuditLogService::created(
            $assignment,
            "Appointed {$assignment->user->name} as acting Subject Officer for {$assignment->subjectCode->code} "
            . "from {$assignment->start_date->format('d M Y')}"
        );

        NotificationService::send(
            $assignment->user,
            type:  'acting_subject_officer_appointed',
            title: 'You have been appointed as acting Subject Officer',
            body:  "You can now submit and manage records for subject code {$assignment->subjectCode->code} "
                 . "starting {$assignment->start_date->format('d M Y')}"
                 . ($assignment->end_date ? " until {$assignment->end_date->format('d M Y')}." : '.'),
            link:  route('dashboard'),
        );

        return redirect()->route('acting-subject-officers.index')
            ->with('success', "{$assignment->user->name} appointed as acting Subject Officer for {$assignment->subjectCode->code}.");
    }

    public function toggle(Request $request, ActingSubjectOfficer $actingSubjectOfficer): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        $actingSubjectOfficer->load(['user', 'subjectCode']);

        return $this->performToggle(
            request:       $request,
            model:         $actingSubjectOfficer,
            label:         "Acting Subject Officer appointment ({$actingSubjectOfficer->user->name} — {$actingSubjectOfficer->subjectCode->code})",
            requireReason: true,
        );
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only Super Admin may appoint acting Subject Officers.');
    }
}
