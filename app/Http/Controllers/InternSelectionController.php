<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Intern;
use App\Models\InternBatch;
use App\Models\InternRotationUnit;
use App\Services\InternAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Public, unauthenticated self-selection portal — an intern picks their
 * own rotation slot rather than an officer assigning it manually.
 *
 * DESIGN ASSUMPTION, stated explicitly because the request didn't specify
 * how selection should actually work and this affects real medical
 * staffing decisions: this implements SELF-SELECT, FIRST-COME-FIRST-
 * SERVED — an intern picks any currently-open slot per appointment, on a
 * first-arrived basis, not a preference-ranking system that gets
 * resolved algorithmically afterward. If preference ranking (rank your
 * top 3, get assigned by seniority/lottery) is what's actually wanted,
 * that is a materially different system and this would need rework, not
 * extension.
 *
 * IDENTITY VERIFICATION: this is a public form for a real staffing
 * decision, so it cannot let anyone type any name and claim a slot as
 * someone else. An intern must be pre-loaded (via the officer's Excel/
 * Word upload) AND confirm the last 4 digits of their NIC before they
 * can select — never the full NIC on a public-facing form. This is a
 * lightweight check, not strong authentication; it deters casual
 * impersonation, not a determined attacker. Verified status is held in
 * the session only (never a persistent login), and expires with it.
 */
class InternSelectionController extends Controller
{
    private const SESSION_KEY = 'verified_intern_id';

    public function show(Request $request, InternBatch $batch)
    {
        $this->assertBatchOpenForSelection($batch);

        $interns = Intern::where('intern_batch_id', $batch->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name'])
            ->sortBy(fn ($intern) => mb_strtolower((string) $intern->name, 'UTF-8'))
            ->values();

        return view('intern-selection.identify', compact('batch', 'interns'));
    }

    public function verify(Request $request, InternBatch $batch)
    {
        $this->assertBatchOpenForSelection($batch);

        $throttleKey = 'intern-verify|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            return back()->with('error', 'Too many attempts. Please wait a minute and try again.');
        }

        $data = $request->validate([
            'intern_id' => ['required', 'integer', 'exists:interns,id'],
            'nic_last_four' => ['required', 'string', 'size:4'],
        ]);

        $intern = Intern::findOrFail($data['intern_id']);

        $storedDigitsOnly = rtrim(strtoupper((string) $intern->nic_number), 'VX');
        $storedLastFour = substr($storedDigitsOnly, -4);

        if ($intern->intern_batch_id !== $batch->id
            || ! $intern->nic_number
            || $storedLastFour !== $data['nic_last_four']
        ) {
            RateLimiter::hit($throttleKey, 60);

            return back()->with('error', 'Name and NIC digits did not match our records for this batch. If your NIC is not on file, contact the intern coordinator.')->withInput();
        }

        RateLimiter::clear($throttleKey);
        $request->session()->put(self::SESSION_KEY, $intern->id);
        $request->session()->put(self::SESSION_KEY.'_batch', $batch->id);

        return redirect()->route('intern-selection.slots', $batch);
    }

    public function slots(Request $request, InternBatch $batch)
    {
        $intern = $this->verifiedIntern($request, $batch);

        $needsFirst = ! $intern->firstAppointment();
        $needsSecond = ! $intern->secondAppointment();

        if (! $needsFirst && ! $needsSecond) {
            return view('intern-selection.complete', compact('batch', 'intern'));
        }

        $rotationUnits = InternRotationUnit::active()->ordered()->get();
        $availability = [];
        foreach ($rotationUnits as $unit) {
            $availability[$unit->id] = [
                1 => InternAssignmentService::remainingCapacity($batch->id, $unit->id, 1),
                2 => InternAssignmentService::remainingCapacity($batch->id, $unit->id, 2),
            ];
        }

        return view('intern-selection.slots', compact('batch', 'intern', 'rotationUnits', 'availability', 'needsFirst', 'needsSecond'));
    }

    public function store(Request $request, InternBatch $batch)
    {
        $intern = $this->verifiedIntern($request, $batch);

        $data = $request->validate([
            'appointment_number' => ['required', 'integer', 'in:1,2'],
            'intern_rotation_unit_id' => ['required', 'integer', 'exists:intern_rotation_units,id'],
        ]);

        $rotationUnit = InternRotationUnit::findOrFail($data['intern_rotation_unit_id']);

        try {
            InternAssignmentService::assign($intern, $rotationUnit, (int) $data['appointment_number'], null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('intern-selection.slots', $batch)
            ->with('success', "You've been assigned to {$rotationUnit->name} for the ".$data['appointment_number'].($data['appointment_number'] == 1 ? 'st' : 'nd').' Appointment.');
    }

    private function verifiedIntern(Request $request, InternBatch $batch): Intern
    {
        $internId = $request->session()->get(self::SESSION_KEY);
        $sessionBatch = $request->session()->get(self::SESSION_KEY.'_batch');

        abort_unless($internId && $sessionBatch === $batch->id, 403, 'Please verify your identity first.');

        $this->assertBatchOpenForSelection($batch);

        return Intern::where('id', $internId)
            ->where('intern_batch_id', $batch->id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function assertBatchOpenForSelection(InternBatch $batch): void
    {
        $today = now()->startOfDay();
        $withinRecordedPeriod = (! $batch->start_date || $today->gte($batch->start_date))
            && (! $batch->end_date || $today->lte($batch->end_date));

        abort_unless(
            $batch->is_active && $withinRecordedPeriod,
            404,
            'This intake batch is not currently open for self-selection.',
        );
    }
}
