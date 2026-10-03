<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiAssistantInteraction;
use App\Models\AiKnowledgeSource;
use App\Services\AdministrativeIntelligenceService;
use App\Services\FeatureToggleService;
use App\Services\OfflineKnowledgeAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfflineAssistantController extends Controller
{
    public function index(Request $request, OfflineKnowledgeAssistantService $assistant): View
    {
        abort_unless(FeatureToggleService::enabled('ai_chat'), 404);

        $phase = max(1, min(4, (int) $request->integer('phase', 1)));
        $this->authorizePhaseAccess($request, $phase);
        $contextRoute = $request->string('context_route')->toString() ?: null;

        return view('ai-assistant.index', [
            'phase' => $phase,
            'contextRoute' => $contextRoute,
            'contextHelp' => $assistant->contextHelp($contextRoute),
            'verifiedSourceCount' => AiKnowledgeSource::query()->where('is_active', true)->where('is_verified', true)->count(),
            'planningAccess' => (bool) $request->user()?->canAccessPlanningReports(),
            'planningSnapshot' => $phase === 4 && $request->user()?->canAccessPlanningReports()
                ? AdministrativeIntelligenceService::summary(now()->year, 12, 0, 0)
                : null,
        ]);
    }

    public function ask(Request $request, OfflineKnowledgeAssistantService $assistant): JsonResponse
    {
        abort_unless(FeatureToggleService::enabled('ai_chat'), 404);

        $data = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:1200'],
            'phase' => ['required', 'integer', 'min:1', 'max:4'],
            'context_route' => ['nullable', 'string', 'max:180'],
            'support_only_acknowledged' => ['nullable', 'boolean'],
        ]);

        $this->authorizePhaseAccess($request, (int) $data['phase']);

        if ((int) $data['phase'] === 4) {
            abort_unless($request->user()?->canAccessPlanningReports(), 403);
            abort_unless(
                (bool) ($data['support_only_acknowledged'] ?? false),
                422,
                'Confirm that Phase 4 is decision support only before asking a planning-intelligence question.'
            );
        }

        $result = $assistant->answer(
            $request->user(),
            $data['question'],
            (int) $data['phase'],
            $data['context_route'] ?? null,
        );

        AiAssistantInteraction::create([
            'user_id' => $request->user()->id,
            'phase' => (int) $data['phase'],
            'question' => $data['question'],
            'context_route' => $data['context_route'] ?? null,
            'answer_mode' => $result['mode'],
            'source_ids' => collect($result['citations'])->pluck('id')->values()->all(),
            'response_summary' => mb_substr($result['answer'], 0, 1000),
            'support_only_acknowledged' => (bool) ($data['support_only_acknowledged'] ?? false),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        return response()->json($result);
    }
    private function authorizePhaseAccess(Request $request, int $phase): void
    {
        $user = $request->user();

        abort_unless($user && $user->is_active, 403, 'Your account is not permitted to use AI Chat.');

        if ($phase === 4) {
            abort_unless(
                $user->canAccessPlanningReports(),
                403,
                'Decision-support Intelligence is limited to Super Admin, Planning Officer, and Medical Officer Planning.'
            );

            return;
        }

        abort_unless(
            $user->hasAnyRole([
                'super_admin',
                'admin_group',
                'planning_officer',
                'unit_manager',
                'subject_officer',
            ]),
            403,
            'You do not have access to AI Chat.'
        );
    }

}
