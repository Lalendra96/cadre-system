<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BusinessRule;
use App\Models\SystemSetting;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GovernanceController extends Controller
{
    public function index(Request $request)
    {
        $rules = BusinessRule::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $profile = [
            'system_owner' => SystemSetting::get(
                'governance_system_owner',
                'Teaching Hospital Peradeniya'
            ),
            'data_controller' => SystemSetting::get(
                'governance_data_controller',
                'To be confirmed by the authorised institution'
            ),
            'decision_authority' => SystemSetting::get(
                'governance_decision_authority',
                'To be confirmed by the authorised institution'
            ),
            'technical_maintainer' => SystemSetting::get(
                'governance_technical_maintainer',
                'Authorised software maintenance provider'
            ),
            'governance_contact' => SystemSetting::get(
                'governance_contact',
                ''
            ),
            'disclaimer_version' => SystemSetting::get(
                'governance_disclaimer_version',
                '1.0'
            ),
        ];

        return view(
            'governance.index',
            compact(
                'rules',
                'profile'
            )
        );
    }

    public function updateProfile(
        Request $request
    ): RedirectResponse {
        abort_unless(
            $request->user()->isSuperAdmin(),
            403
        );

        $data = $request->validate([
            'system_owner' => [
                'required',
                'string',
                'max:180',
            ],
            'data_controller' => [
                'required',
                'string',
                'max:220',
            ],
            'decision_authority' => [
                'required',
                'string',
                'max:220',
            ],
            'technical_maintainer' => [
                'required',
                'string',
                'max:220',
            ],
            'governance_contact' => [
                'nullable',
                'string',
                'max:220',
            ],
            'disclaimer_version' => [
                'required',
                'string',
                'max:30',
            ],
        ]);

        SystemSetting::setMany([
            'governance_system_owner' => $data['system_owner'],
            'governance_data_controller' => $data['data_controller'],
            'governance_decision_authority' => $data['decision_authority'],
            'governance_technical_maintainer' => $data['technical_maintainer'],
            'governance_contact' => $data['governance_contact'] ?? '',
            'governance_disclaimer_version' => $data['disclaimer_version'],
        ]);

        return back()->with(
            'success',
            'Governance profile updated. Confirm these designations with the authorised institutional/legal officer before relying on them.'
        );
    }

    public function storeRule(
        Request $request
    ): RedirectResponse {
        abort_unless(
            $request->user()->isSuperAdmin(),
            403
        );

        $data = $this->validatedRule($request);

        $rule = BusinessRule::create(
            $data + [
                'is_active' => true,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]
        );

        AuditLogService::created(
            $rule,
            'Created governance business rule. No rule is authoritative merely because it exists in the application.'
        );

        return back()->with(
            'success',
            'Business rule added to the Governance Register.'
        );
    }

    public function updateRule(
        Request $request,
        BusinessRule $businessRule
    ): RedirectResponse {
        abort_unless(
            $request->user()->isSuperAdmin(),
            403
        );

        $old = $businessRule->getOriginal();
        $data = $this->validatedRule(
            $request,
            $businessRule
        );

        $businessRule->update(
            $data + [
                'updated_by' => $request->user()->id,
            ]
        );

        AuditLogService::updated(
            $businessRule,
            $old,
            'Updated governance business rule/source reference.'
        );

        return back()->with(
            'success',
            'Business rule updated.'
        );
    }

    public function toggleRule(
        Request $request,
        BusinessRule $businessRule
    ): RedirectResponse {
        abort_unless(
            $request->user()->isSuperAdmin(),
            403
        );

        $old = $businessRule->getOriginal();

        $businessRule->update([
            'is_active' => ! $businessRule->is_active,
            'updated_by' => $request->user()->id,
        ]);

        AuditLogService::updated(
            $businessRule,
            $old,
            'Changed governance business rule active status.'
        );

        return back()->with(
            'success',
            'Business rule status updated.'
        );
    }

    private function validatedRule(
        Request $request,
        ?BusinessRule $businessRule = null
    ): array {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique(
                    'business_rules',
                    'code'
                )->ignore($businessRule?->id),
            ],
            'name' => [
                'required',
                'string',
                'max:160',
            ],
            'system_behavior' => [
                'required',
                'string',
                'max:3000',
            ],
            'authority_type' => [
                'required',
                Rule::in(
                    array_keys(
                        BusinessRule::AUTHORITY_TYPES
                    )
                ),
            ],
            'authority_reference' => [
                'nullable',
                'string',
                'max:180',
            ],
            'authority_title' => [
                'nullable',
                'string',
                'max:300',
            ],
            'authority_url' => [
                'nullable',
                'url',
                'max:500',
            ],
            'effective_date' => [
                'nullable',
                'date',
            ],
            'review_due_date' => [
                'nullable',
                'date',
            ],
            'approved_by_name' => [
                'nullable',
                'string',
                'max:180',
            ],
            'approved_by_designation' => [
                'nullable',
                'string',
                'max:180',
            ],
            'approval_reference' => [
                'nullable',
                'string',
                'max:180',
            ],
            'status' => [
                'required',
                Rule::in(
                    array_keys(
                        BusinessRule::STATUSES
                    )
                ),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);
    }
}
