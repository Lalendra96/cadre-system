<?php

namespace App\Services;

use App\Models\GovernanceActionAttestation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GovernanceAttestationService
{
    public static function record(
        Request $request,
        string $actionKey,
        string $resourceType,
        ?int $resourceId,
        array $data
    ): void {
        if (! Schema::hasTable('governance_action_attestations')) {
            return;
        }

        GovernanceActionAttestation::create([
            'user_id' => $request->user()->id,
            'action_key' => $actionKey,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'administrative_purpose' => trim((string) ($data['administrative_purpose'] ?? 'Official administrative action.')),
            'authority_reference' => isset($data['authority_reference']) ? trim((string) $data['authority_reference']) : null,
            'evidence_reviewed' => (bool) ($data['evidence_reviewed'] ?? false),
            'accuracy_confirmed' => (bool) ($data['accuracy_confirmed'] ?? false),
            'minimum_necessary_confirmed' => (bool) ($data['minimum_necessary_confirmed'] ?? false),
            'no_conflict_confirmed' => (bool) ($data['no_conflict_confirmed'] ?? false),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'attested_at' => now(),
        ]);
    }
}
