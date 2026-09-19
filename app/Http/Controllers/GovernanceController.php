<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BusinessRule;
use App\Models\BusinessRuleVersion;
use App\Models\ConfigurationChangeLog;
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
            ->with([
                'versions.changedBy',
            ])
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
            'confirmed_by' => SystemSetting::get(
                'governance_profile_confirmed_by',
                ''
            ),
            'confirmed_designation' => SystemSetting::get(
                'governance_profile_confirmed_designation',
                ''
            ),
            'confirmed_at' => SystemSetting::get(
                'governance_profile_confirmed_at',
                ''
            ),
            'confirmation_reference' => SystemSetting::get(
                'governance_profile_confirmation_reference',
                ''
            ),
        ];

        $configurationChanges = ConfigurationChangeLog::query()
            ->with('changedBy')
            ->latest('changed_at')
            ->limit(100)
            ->get();

        return view(
            'governance.index',
            compact(
                'rules',
                'profile',
                'configurationChanges'
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
            'confirmed_by' => [
                'required',
                'string',
                'max:180',
            ],
            'confirmed_designation' => [
                'required',
                'string',
                'max:180',
            ],
            'confirmation_reference' => [
                'nullable',
                'string',
                'max:180',
            ],
            'confirm_institutional_review' => [
                'accepted',
            ],
        ]);

        SystemSetting::setMany([
            'governance_system_owner' => $data['system_owner'],
            'governance_data_controller' => $data['data_controller'],
            'governance_decision_authority' => $data['decision_authority'],
            'governance_technical_maintainer' => $data['technical_maintainer'],
            'governance_contact' => $data['governance_contact'] ?? '',
            'governance_disclaimer_version' => $data['disclaimer_version'],
            'governance_profile_confirmed_by' => $data['confirmed_by'],
            'governance_profile_confirmed_designation'
                => $data['confirmed_designation'],
            'governance_profile_confirmed_at' => now()->toIso8601String(),
            'governance_profile_confirmation_reference'
                => $data['confirmation_reference'] ?? '',
        ]);

        return back()->with(
            'success',
            'Governance responsibility profile recorded as institutionally reviewed. This documents the institution\'s designation; the software itself does not create or determine a legal controller/processor relationship.'
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
        $changeReason = $data['change_reason']
            ?? 'Initial rule registration.';

        unset($data['change_reason']);

        $rule = BusinessRule::create(
            $data + [
                'is_active' => true,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]
        );

        $this->recordRuleVersion(
            $rule,
            'created',
            $request->user()->id,
            $changeReason
        );

        AuditLogService::created(
            $rule,
            'Created governance business rule. A rule is not authoritative merely because it exists in the application.'
        );

        return back()->with(
            'success',
            'Business rule added to the Governance Register with version history.'
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

        $data = $this->validatedRule(
            $request,
            $businessRule
        );

        $request->validate([
            'change_reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ]);

        $changeReason = $data['change_reason'];
        unset($data['change_reason']);

        $old = $businessRule->getOriginal();

        $businessRule->update(
            $data + [
                'updated_by' => $request->user()->id,
            ]
        );

        $this->recordRuleVersion(
            $businessRule,
            'updated',
            $request->user()->id,
            $changeReason
        );

        AuditLogService::updated(
            $businessRule,
            $old,
            'Updated governance business rule/source reference: '
                . $changeReason
        );

        return back()->with(
            'success',
            'Business rule updated and a new immutable history version was recorded.'
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

        $data = $request->validate([
            'change_reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ]);

        $old = $businessRule->getOriginal();

        $businessRule->update([
            'is_active' => ! $businessRule->is_active,
            'updated_by' => $request->user()->id,
        ]);

        $this->recordRuleVersion(
            $businessRule,
            $businessRule->is_active
                ? 'enabled'
                : 'disabled',
            $request->user()->id,
            $data['change_reason']
        );

        AuditLogService::updated(
            $businessRule,
            $old,
            'Changed governance business rule active status: '
                . $data['change_reason']
        );

        return back()->with(
            'success',
            'Business rule status updated and retained in immutable rule history.'
        );
    }

    public function ruleHistory(
        Request $request,
        BusinessRule $businessRule
    ) {
        abort_unless(
            $request->user()->isSuperAdmin(),
            403
        );

        $businessRule->load([
            'versions.changedBy',
        ]);

        return view(
            'governance.rule-history',
            [
                'rule' => $businessRule,
            ]
        );
    }

    private function recordRuleVersion(
        BusinessRule $rule,
        string $changeType,
        ?int $userId,
        ?string $reason
    ): void {
        $nextVersion = (
            (int) $rule->versions()->max('version_no')
        ) + 1;

        BusinessRuleVersion::create([
            'business_rule_id' => $rule->id,
            'version_no' => $nextVersion,
            'change_type' => $changeType,
            'snapshot' => $rule->fresh()->toArray(),
            'changed_by' => $userId,
            'change_reason' => $reason,
            'created_at' => now(),
        ]);
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
                'required_if:status,source_verified',
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
                'required_if:status,source_verified',
                'nullable',
                'string',
                'max:180',
            ],
            'approved_by_designation' => [
                'required_if:status,source_verified',
                'nullable',
                'string',
                'max:180',
            ],
            'approval_reference' => [
                'required_if:status,source_verified',
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
            'change_reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);
    }
}
