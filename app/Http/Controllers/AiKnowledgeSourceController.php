<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiKnowledgeSource;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AiKnowledgeSourceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeManagement($request);

        $query = AiKnowledgeSource::query()->latest();

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->string('source_type'));
        }

        if ($request->filled('status')) {
            $request->string('status')->toString() === 'active'
                ? $query->where('is_active', true)
                : $query->where('is_active', false);
        }

        return view('ai-assistant.sources.index', [
            'sources' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeManagement($request);

        return view('ai-assistant.sources.form', [
            'source' => new AiKnowledgeSource(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManagement($request);
        $data = $this->validated($request);
        $data['uploaded_by'] = $request->user()->id;
        $data['is_verified'] = false;
        $data['is_active'] = true;

        $source = AiKnowledgeSource::create($data);
        AuditLogService::created($source, 'Created offline assistant knowledge source.');

        return redirect()
            ->route('offline-assistant.sources.index')
            ->with('success', 'Knowledge source added as unverified. Verify it before it can be used for governed KB answers.');
    }

    public function verify(Request $request, AiKnowledgeSource $source): RedirectResponse
    {
        $this->authorizeManagement($request);
        $request->validate([
            'verification_confirmed' => ['accepted'],
        ]);

        $old = $source->toArray();
        $source->update([
            'is_verified' => true,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        AuditLogService::updated($source, $old, 'Verified source for governed offline assistant retrieval.');

        return back()->with('success', 'Source verified for governed retrieval.');
    }

    public function disable(Request $request, AiKnowledgeSource $source): RedirectResponse
    {
        $this->authorizeManagement($request);
        $data = $request->validate([
            'disable_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $old = $source->toArray();
        $source->update(['is_active' => false]);

        AuditLogService::updated(
            $source,
            $old,
            'Disabled knowledge source: '.$data['disable_reason'],
        );

        return back()->with('success', 'Source disabled. Existing audit records are preserved.');
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless(
            $request->user()?->isSuperAdmin(),
            403,
            'Knowledge-source governance is limited to Super Admin. Other roles may use only sources already approved for their assistant access.'
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:220'],
            'source_type' => ['required', 'in:application_help,user_manual,policy,circular,governance,planning_guideline,procedure'],
            'language' => ['required', 'in:en,si,ta'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'issuing_authority' => ['nullable', 'string', 'max:180'],
            'effective_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'classification' => ['required', 'in:public,internal,confidential'],
            'content' => ['required', 'string', 'min:20'],
            'source_location' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
