<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDisableToggle;
use App\Models\Position;
use App\Models\PositionActingAllowanceRule;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Super-Admin-only configuration of acting allowance calculation rules,
 * one per Position. See PositionActingAllowanceRule / ActingAllowanceCalculator
 * for the three supported rule types and how each is computed.
 */
class PositionActingAllowanceRuleController extends Controller
{
    use HandlesDisableToggle;

    public function index(Request $request)
    {
        $this->authorizeSuperAdmin($request);

        $positions = Position::active()
            ->with('actingAllowanceRule')
            ->orderBy('title')
            ->get();

        return view('position-acting-allowance-rules.index', compact('positions'));
    }

    public function create(Request $request, Position $position)
    {
        $this->authorizeSuperAdmin($request);

        if ($position->actingAllowanceRule) {
            return redirect()->route('position-acting-allowance-rules.edit', $position)
                ->with('error', 'A rule already exists for this position — edit it instead.');
        }

        return view('position-acting-allowance-rules.form', [
            'position' => $position,
            'rule'     => new PositionActingAllowanceRule(),
        ]);
    }

    public function store(Request $request, Position $position): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        if ($position->actingAllowanceRule) {
            return redirect()->route('position-acting-allowance-rules.edit', $position)
                ->with('error', 'A rule already exists for this position — edit it instead of creating a new one.');
        }

        $data = $this->validateRule($request);
        $data['position_id'] = $position->id;
        $data['created_by']  = $request->user()->id;
        $data['is_active']   = true;

        $rule = PositionActingAllowanceRule::create($data);

        AuditLogService::created($rule, "Configured acting allowance rule for {$position->title}: {$rule->rule_type}");

        return redirect()->route('position-acting-allowance-rules.index')
            ->with('success', "Acting allowance rule for {$position->title} created.");
    }

    public function edit(Request $request, Position $position)
    {
        $this->authorizeSuperAdmin($request);

        $rule = $position->actingAllowanceRule;
        abort_unless($rule, 404, 'No rule configured for this position yet.');

        return view('position-acting-allowance-rules.form', compact('position', 'rule'));
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $rule = $position->actingAllowanceRule;
        abort_unless($rule, 404);

        $data = $this->validateRule($request);
        $old  = $rule->getOriginal();
        $rule->update($data);

        AuditLogService::updated($rule, $old, "Updated acting allowance rule for {$position->title}");

        return redirect()->route('position-acting-allowance-rules.index')
            ->with('success', "Acting allowance rule for {$position->title} updated.");
    }

    public function toggle(Request $request, Position $position): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $rule = $position->actingAllowanceRule;
        abort_unless($rule, 404);

        return $this->performToggle(
            request:       $request,
            model:         $rule,
            label:         "Acting allowance rule for \"{$position->title}\"",
            requireReason: false,
        );
    }

    private function validateRule(Request $request): array
    {
        $validated = $request->validate([
            'rule_type'   => ['required', Rule::in(array_keys(PositionActingAllowanceRule::RULE_TYPE_LABELS))],
            'percentage'  => ['nullable', 'numeric', 'min:0', 'max:100', 'required_unless:rule_type,flat_amount'],
            'flat_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99', 'required_if:rule_type,flat_amount'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        // Only persist the field relevant to the chosen rule type, so a
        // stale percentage/flat_amount from a prior edit never lingers
        // and gets mistakenly used if the rule_type is later switched back.
        if ($validated['rule_type'] === PositionActingAllowanceRule::RULE_TYPE_FLAT) {
            $validated['percentage'] = null;
        } else {
            $validated['flat_amount'] = null;
        }

        return $validated;
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only Super Admin may configure acting allowance rules.');
    }
}
