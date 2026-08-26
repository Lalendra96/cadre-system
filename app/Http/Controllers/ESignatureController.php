<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ESignature;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Registers and serves e-signature images for Admin Group users, used
 * when approving Service Letters and Vacancy Availability Letters.
 *
 * DATA SECURITY (read before touching this controller):
 *   - Uploaded files are stored on the `local` (private) disk under
 *     `signatures/`, NEVER on `public`. There is no direct URL to a
 *     signature file — show() is the only path to the image bytes, and
 *     it re-checks the requester is either the owner or Super Admin.
 *   - Registering a new signature disables (not deletes) any previous
 *     active one for the user, so documents already signed keep a valid
 *     reference to the exact image that was in effect when they were signed.
 */
class ESignatureController extends Controller
{
    public function edit(Request $request)
    {
        $this->authorizeAdminGroup($request);

        $current = $request->user()->eSignature;

        return view('e-signatures.edit', compact('current'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdminGroup($request);

        $request->validate([
            'signature' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:512'], // 512KB — small, transparent PNG expected
        ], [
            'signature.max' => 'Signature image must be under 512KB. A small transparent PNG works best.',
        ]);

        $user = $request->user();
        $file = $request->file('signature');

        $path = $file->storeAs('signatures', $user->id . '_' . Str::random(12) . '.' . $file->extension(), 'local');

        // Disable the previous active signature (if any) rather than deleting
        // it, so any letter already signed with it still resolves correctly.
        if ($previous = $user->eSignature) {
            $previous->disableRecord($user->id, 'Replaced by a newly-uploaded signature.');
        }

        $signature = ESignature::create([
            'user_id'   => $user->id,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'is_active' => true,
        ]);

        AuditLogService::created($signature, "Registered a new e-signature for {$user->name}");

        return redirect()->route('e-signatures.edit')->with('success', 'Your e-signature has been registered.');
    }

    /**
     * Serves the raw signature image bytes. Access is restricted to the
     * signature's own owner or Super Admin — this is the ONLY way to read
     * a signature file; the private disk path is never exposed directly.
     */
    public function show(Request $request, ESignature $eSignature): Response
    {
        $user = $request->user();
        abort_unless(
            $eSignature->user_id === $user->id || $user->isSuperAdmin(),
            403,
            'You are not authorised to view this signature.'
        );
        abort_unless($eSignature->fileExists(), 404);

        return response()->file(Storage::disk('local')->path($eSignature->file_path));
    }

    private function authorizeAdminGroup(Request $request): void
    {
        abort_unless(
            $request->user()->isAdminGroup() || $request->user()->isSuperAdmin(),
            403,
            'E-signatures are for Admin Group approvers.'
        );
    }
}
