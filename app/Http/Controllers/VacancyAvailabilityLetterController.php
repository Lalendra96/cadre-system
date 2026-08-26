<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\SubjectCode;
use App\Models\VacancyAvailabilityLetter;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Vacancy Availability Letters — a formal, e-signed declaration that a
 * position under a subject code has a recruitable vacancy.
 *
 * BUSINESS RULE — 90-day cooldown: the SAME position+subject_code pair
 * cannot receive a new letter while an existing one's cooldown_until is
 * still in the future. See store() for the enforcement query, and the
 * VacancyAvailabilityLetter model docblock for why the same field also
 * serves as "what Subject Officers currently see as available."
 *
 * ACCESS:
 *   Issue (store):  Admin Group (Administrative Officer / Director) or
 *                    Super Admin — requires the issuer to have a
 *                    registered e-signature already on file.
 *   View (index):    Everyone with a role — Subject Officers see only
 *                    letters for their own effective subject codes;
 *                    Planning/Admin/Super Admin see all.
 */
class VacancyAvailabilityLetterController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = VacancyAvailabilityLetter::active()
            ->with(['position', 'subjectCode', 'issuedBy'])
            ->orderByDesc('issued_at');

        if ($user->isSubjectOfficer() && ! $user->isSuperAdmin() && ! $user->isPlanningOfficer() && ! $user->isAdminGroup()) {
            $codeIds = $user->effectiveSubjectCodeIds();
            $query->whereIn('subject_code_id', $codeIds);
        }

        $onlyAvailable = $request->boolean('only_available', true);
        if ($onlyAvailable) {
            $query->currentlyAvailable();
        }

        $letters = $query->paginate(20)->withQueryString();

        return view('vacancy-availability-letters.index', compact('letters', 'onlyAvailable'));
    }

    public function create(Request $request)
    {
        $this->authorizeIssuer($request);

        $positions    = Position::active()->orderBy('title')->get();
        $subjectCodes = SubjectCode::active()->orderBy('code')->get();

        return view('vacancy-availability-letters.form', compact('positions', 'subjectCodes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeIssuer($request);

        $user = $request->user();
        $signature = $user->eSignature;
        if (! $signature) {
            return redirect()->route('e-signatures.edit')
                ->with('error', 'Please register your e-signature before issuing a vacancy availability letter.');
        }

        $data = $request->validate([
            'position_id'     => ['required', 'integer', 'exists:positions,id'],
            'subject_code_id' => ['required', 'integer', 'exists:subject_codes,id'],
            'vacancy_count'   => ['required', 'integer', 'min:1', 'max:999'],
            'reference_no'    => ['nullable', 'string', 'max:60'],
            'remarks'         => ['nullable', 'string', 'max:1000'],
            'confirm_signature' => ['accepted'],
        ], [
            'confirm_signature.accepted' => 'You must confirm you are e-signing this letter.',
        ]);

        // ── 90-day cooldown enforcement ──────────────────────────────────
        $existing = VacancyAvailabilityLetter::forPosition($data['position_id'], $data['subject_code_id'])
            ->currentlyAvailable()
            ->orderByDesc('cooldown_until')
            ->first();

        if ($existing) {
            return back()->withInput()->with('error',
                'A vacancy availability letter for this position and subject code is still active until '
                . $existing->cooldown_until->format('d M Y')
                . ' (' . $existing->daysRemainingInCooldown() . ' day(s) remaining). '
                . 'A new letter cannot be issued until then.'
            );
        }

        $letter = VacancyAvailabilityLetter::create([
            'position_id'     => $data['position_id'],
            'subject_code_id' => $data['subject_code_id'],
            'vacancy_count'   => $data['vacancy_count'],
            'reference_no'    => $data['reference_no'] ?? null,
            'remarks'         => $data['remarks'] ?? null,
            'issued_by'       => $user->id,
            'issued_at'       => now(),
            'e_signature_id'  => $signature->id,
            'cooldown_until'  => now()->addDays(90)->toDateString(),
            'is_active'       => true,
        ]);
        $letter->load(['position', 'subjectCode']);

        AuditLogService::created(
            $letter,
            "E-signed vacancy availability letter issued by {$user->name}: {$letter->vacancy_count} vacancy(ies) "
            . "for {$letter->position->title} under {$letter->subjectCode->code}, valid until {$letter->cooldown_until->format('d M Y')}"
        );

        // Notify subject officers currently assigned to this subject code.
        $recipients = \App\Models\User::active()
            ->whereHas('subjectCodes', fn ($q) => $q->where('subject_codes.id', $data['subject_code_id']))
            ->get();

        NotificationService::sendToMany(
            $recipients,
            type:  'vacancy_available',
            title: 'New vacancy available',
            body:  "{$letter->vacancy_count} vacancy(ies) now available for {$letter->position->title} ({$letter->subjectCode->code}).",
            link:  route('vacancy-availability-letters.index'),
        );

        return redirect()->route('vacancy-availability-letters.index')
            ->with('success', "Vacancy availability letter issued. Valid until {$letter->cooldown_until->format('d M Y')}.");
    }

    private function authorizeIssuer(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user->isSuperAdmin() || $user->isAdminGroup(),
            403,
            'Only Admin Group (e.g. Administrative Officer, Director) or Super Admin may issue a vacancy availability letter.'
        );
    }
}
