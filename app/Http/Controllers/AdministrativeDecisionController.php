<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AdministrativeDecision;
use App\Services\AdministrativeDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdministrativeDecisionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = AdministrativeDecision::query()
            ->with([
                'employee.position',
                'requester',
                'decider',
                'businessRule',
            ])
            ->latest('requested_at');

        if (
            ! $user->isSuperAdmin()
            && ! $user->isAdministrativeOfficer()
        ) {
            $query->where(
                'requested_by',
                $user->id
            );
        }

        if ($status = $request->query('status')) {
            $query->where(
                'status',
                $status
            );
        }

        $decisions = $query
            ->paginate(25)
            ->withQueryString();

        return view(
            'administrative-decisions.index',
            compact('decisions')
        );
    }

    public function show(
        Request $request,
        AdministrativeDecision $administrativeDecision
    ) {
        $this->authorizeView(
            $request,
            $administrativeDecision
        );

        $administrativeDecision->load([
            'employee.position',
            'requester',
            'decider',
            'businessRule',
            'events.actor',
        ]);

        return view(
            'administrative-decisions.show',
            [
                'decision' => $administrativeDecision,
            ]
        );
    }

    public function approve(
        Request $request,
        AdministrativeDecision $administrativeDecision
    ): RedirectResponse {
        $data = $request->validate([
            'decision_reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'confirm_authority_checked' => [
                'accepted',
            ],
        ]);

        AdministrativeDecisionService::approve(
            $administrativeDecision,
            $request->user(),
            $data['decision_reason']
        );

        return redirect()
            ->route(
                'administrative-decisions.show',
                $administrativeDecision
            )
            ->with(
                'success',
                'Decision approved and applied. The approval, authority check and resulting record have been retained in the decision history.'
            );
    }

    public function reject(
        Request $request,
        AdministrativeDecision $administrativeDecision
    ): RedirectResponse {
        $data = $request->validate([
            'decision_reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ]);

        AdministrativeDecisionService::reject(
            $administrativeDecision,
            $request->user(),
            $data['decision_reason']
        );

        return redirect()
            ->route(
                'administrative-decisions.show',
                $administrativeDecision
            )
            ->with(
                'success',
                'Decision request rejected. No consequential HR record was applied.'
            );
    }

    private function authorizeView(
        Request $request,
        AdministrativeDecision $decision
    ): void {
        $user = $request->user();

        abort_unless(
            $user->isSuperAdmin()
                || $user->isAdministrativeOfficer()
                || $decision->requested_by === $user->id,
            403
        );
    }
}
