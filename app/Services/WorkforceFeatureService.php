<?php

namespace App\Services;

class WorkforceFeatureService
{
    public const FEATURES = [
        'roster' => 'Roster',
        'attendance' => 'Attendance',
        'biometric_attendance' => 'Biometric / Multi-Punch Attendance',
        'leave' => 'Leave',
        'leave_automation' => 'Leave Accrual / Carry-Forward Support',
        'overtime' => 'Overtime',
        'contracts' => 'Contracts',
        'contract_lifecycle' => 'Contract Renewal / Probation',
        'payroll' => 'Payroll',
        'self_service' => 'Employee Self-Service',
        'locum' => 'Locum / Sessions',
        'locum_pool' => 'Locum Pool / Booking',
        'demand_forecasting' => 'Workforce Demand Forecasting',
        'roster_optimizer' => 'Advanced Roster Optimization',
        'cost_centre' => 'Cost Centre Analytics',
    ];

    private const FEATURE_MAP = [
        'roster' => 'roster',
        'attendance' => 'attendance',
        'biometric_attendance' => 'biometric_attendance',
        'leave' => 'leave_management',
        'leave_automation' => 'leave_automation',
        'overtime' => 'overtime',
        'contracts' => 'contracts',
        'contract_lifecycle' => 'contract_lifecycle',
        'payroll' => 'payroll',
        'self_service' => 'employee_self_service',
        'locum' => 'locum_sessions',
        'locum_pool' => 'locum_pool',
        'demand_forecasting' => 'demand_forecasting',
        'roster_optimizer' => 'roster_optimizer',
        'cost_centre' => 'cost_centre_analytics',
    ];

    public function enabled(string $feature): bool
    {
        $mapped = self::FEATURE_MAP[$feature] ?? null;
        return $mapped ? FeatureToggleService::enabled($mapped) : false;
    }

    public function settingKey(string $feature): ?string
    {
        $mapped = self::FEATURE_MAP[$feature] ?? null;
        return $mapped ? (FeatureToggleService::FEATURES[$mapped]['key'] ?? null) : null;
    }

    public function dependencies(string $feature): array
    {
        return match ($feature) {
            'overtime' => ['attendance'],
            'biometric_attendance' => ['attendance'],
            'leave_automation' => ['leave'],
            'contract_lifecycle' => ['contracts'],
            'payroll' => ['contracts'],
            'locum' => ['contracts'],
            'locum_pool' => ['locum', 'contracts'],
            'roster_optimizer' => ['roster'],
            'cost_centre' => ['payroll'],
            default => [],
        };
    }
}
