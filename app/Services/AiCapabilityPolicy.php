<?php

declare(strict_types=1);

namespace App\Services;

use LogicException;

class AiCapabilityPolicy
{
    public const ALLOWED_CAPABILITIES = [
        'record_summary',
        'draft_service_letter',
    ];

    public const FORBIDDEN_CAPABILITIES = [
        'approve_hr_decision',
        'change_grade',
        'approve_transfer',
        'approve_increment',
        'approve_retirement',
        'change_employment_status',
        'apply_disciplinary_outcome',
        'sign_official_document',
        'mutate_employee_record',
    ];

    public static function assertAdvisory(
        string $capability
    ): void {
        if (! in_array(
            $capability,
            self::ALLOWED_CAPABILITIES,
            true
        )) {
            throw new LogicException(
                'Carder AI is advisory-only and is not permitted to execute or approve consequential HR actions.'
            );
        }
    }
}
