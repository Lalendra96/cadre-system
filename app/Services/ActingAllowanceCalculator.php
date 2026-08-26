<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActingAppointment;
use App\Models\PositionActingAllowanceRule;

/**
 * Computes the acting allowance for a given ActingAppointment based on
 * the Super-Admin-configured PositionActingAllowanceRule for the position
 * being acted in.
 *
 * Returns a structured result rather than a bare number so the caller
 * (and the view) can distinguish "computed Rs. 0" from "not configured"
 * from "cannot compute — missing salary scale data" — three genuinely
 * different states that a single nullable float would collapse together
 * and mislead whoever's reading a payroll-adjacent figure.
 */
class ActingAllowanceCalculator
{
    public const STATUS_OK               = 'ok';
    public const STATUS_NOT_CONFIGURED   = 'not_configured';
    public const STATUS_MISSING_DATA     = 'missing_data';

    /**
     * @return object{status: string, amount: ?float, message: ?string, rule: ?PositionActingAllowanceRule}
     */
    public static function calculate(ActingAppointment $appointment): object
    {
        $rule = PositionActingAllowanceRule::active()
            ->where('position_id', $appointment->acting_position_id)
            ->first();

        if (! $rule) {
            return (object) [
                'status'  => self::STATUS_NOT_CONFIGURED,
                'amount'  => null,
                'message' => 'No acting allowance rule configured for this position.',
                'rule'    => null,
            ];
        }

        return match ($rule->rule_type) {
            PositionActingAllowanceRule::RULE_TYPE_FLAT => (object) [
                'status'  => self::STATUS_OK,
                'amount'  => (float) $rule->flat_amount,
                'message' => null,
                'rule'    => $rule,
            ],

            PositionActingAllowanceRule::RULE_TYPE_PCT_BASE => self::percentageOfBase($appointment, $rule),

            PositionActingAllowanceRule::RULE_TYPE_PCT_DIFFERENCE => self::percentageOfDifference($appointment, $rule),

            default => (object) [
                'status'  => self::STATUS_NOT_CONFIGURED,
                'amount'  => null,
                'message' => "Unrecognised rule type \"{$rule->rule_type}\".",
                'rule'    => $rule,
            ],
        };
    }

    private static function percentageOfBase(ActingAppointment $appointment, PositionActingAllowanceRule $rule): object
    {
        $actingScale = self::anySalaryScaleForPosition($appointment->acting_position_id);

        if (! $actingScale || $actingScale->min_salary === null || $actingScale->max_salary === null) {
            return (object) [
                'status'  => self::STATUS_MISSING_DATA,
                'amount'  => null,
                'message' => 'The acting position has no salary scale with min/max salary configured — cannot compute a percentage-based allowance.',
                'rule'    => $rule,
            ];
        }

        $midpoint = ((float) $actingScale->min_salary + (float) $actingScale->max_salary) / 2;
        $amount   = round($midpoint * ((float) $rule->percentage / 100), 2);

        return (object) ['status' => self::STATUS_OK, 'amount' => $amount, 'message' => null, 'rule' => $rule];
    }

    private static function percentageOfDifference(ActingAppointment $appointment, PositionActingAllowanceRule $rule): object
    {
        $actingScale      = self::anySalaryScaleForPosition($appointment->acting_position_id);
        $substantiveScale = self::anySalaryScaleForPosition($appointment->substantive_position_id);

        if (! $actingScale || ! $substantiveScale
            || $actingScale->min_salary === null || $actingScale->max_salary === null
            || $substantiveScale->min_salary === null || $substantiveScale->max_salary === null) {
            return (object) [
                'status'  => self::STATUS_MISSING_DATA,
                'amount'  => null,
                'message' => 'Both the acting and substantive positions need a salary scale with min/max salary configured to compute a salary-difference-based allowance.',
                'rule'    => $rule,
            ];
        }

        $actingMid      = ((float) $actingScale->min_salary + (float) $actingScale->max_salary) / 2;
        $substantiveMid = ((float) $substantiveScale->min_salary + (float) $substantiveScale->max_salary) / 2;
        $difference     = max($actingMid - $substantiveMid, 0); // never negative — acting "down" earns no allowance

        $amount = round($difference * ((float) $rule->percentage / 100), 2);

        return (object) ['status' => self::STATUS_OK, 'amount' => $amount, 'message' => null, 'rule' => $rule];
    }

    /**
     * Best-effort lookup of a representative salary scale for a position —
     * via any currently active employee holding it. This is a pragmatic
     * approximation: positions don't have a single canonical salary scale
     * of their own in this schema (scales are recorded per-employee, since
     * two officers in the same post can sit at different points on a
     * scale), so "the scale most employees in this position actually use"
     * is the most defensible stand-in available without a larger schema
     * change to give Position its own scale-range field.
     */
    private static function anySalaryScaleForPosition(int $positionId)
    {
        return \App\Models\Employee::active()
            ->where('position_id', $positionId)
            ->whereNotNull('salary_scale_id')
            ->with('salaryScale')
            ->first()
            ?->salaryScale;
    }
}
