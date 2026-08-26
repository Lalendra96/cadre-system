<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Generates a plain-language executive summary of a unit breakdown
 * calculation — the "AI text summarization" for the Director/Deputy
 * Director/DDG executive view.
 *
 * DESIGN CHOICE — deliberately rule-based, not an LLM API call:
 * This system holds hospital workforce data (headcounts, unit names,
 * vacancy figures tied to specific positions). Sending that to an
 * external AI API would mean HR/establishment data leaving the hospital's
 * infrastructure to a third party — that is a real data-governance
 * decision, not something to wire up silently as a side effect of a
 * "add AI summarization" request. This service produces the same
 * *kind* of output (a fluent narrative digest of the numbers) entirely
 * in-process, with zero external calls and zero data leaving the server.
 *
 * If genuine LLM-generated narrative is wanted instead, that needs an
 * explicit decision: which provider, an API key stored securely, and a
 * sign-off that hospital headcount data may be sent to it. This service
 * is written so swapping it out later is a single call-site change —
 * summarize() has one job and one return type (a plain string), so a
 * future LlmUnitBreakdownNarrativeService could implement the exact same
 * interface without touching the controller or view at all.
 */
class UnitBreakdownNarrativeService
{
    public static function summarize(object $calc, int $year): string
    {
        $rows = $calc->rows;

        if ($rows->isEmpty()) {
            return "No approved carder or headcount data is available for {$year} yet — "
                 . 'a summary will appear here once Approved Carder figures and Unit Post Allocations are entered.';
        }

        $totalApproved = $rows->sum('approvedAmt');
        $totalActual   = $rows->sum('totalInPos');
        $totalVacancy  = $rows->sum('vacancy');
        $overallFillPct = $totalApproved > 0 ? (int) round($totalActual / $totalApproved * 100) : 0;

        $critical = $rows->filter(fn ($r) =>
            $r->approvedAmt > 0 && ($r->vacancy / max($r->approvedAmt, 1)) * 100 >= $calc->threshold
        )->sortByDesc('vacancy')->values();

        // The position with the largest absolute vacancy is the single
        // number a Director is most likely to ask about first. We report
        // at the position level (not per-unit) because approved carder is
        // tracked per position hospital-wide, not split per unit — naming
        // a specific unit here would overclaim precision the underlying
        // data doesn't actually carry.
        $worstPosition = $critical->first();

        $parts = [];

        $parts[] = sprintf(
            'As of %s, the hospital has %s of %s approved posts filled across %d position(s) with recorded activity — an overall fill rate of %d%%, leaving %s vacant post(s).',
            now()->format('d M Y'),
            number_format($totalActual),
            number_format($totalApproved),
            $rows->count(),
            $overallFillPct,
            number_format($totalVacancy)
        );

        if ($critical->isEmpty()) {
            $parts[] = "No position is currently below the {$calc->threshold}% vacancy alert threshold — staffing levels are within the configured tolerance hospital-wide.";
        } else {
            $names = $critical->take(3)->map(fn ($r) => "{$r->position->title} ({$r->vacancy} vacant, {$r->fillPct}% filled)")->implode('; ');
            $parts[] = sprintf(
                '%d position(s) are below the %d%% vacancy alert threshold: %s%s.',
                $critical->count(),
                $calc->threshold,
                $names,
                $critical->count() > 3 ? ', and ' . ($critical->count() - 3) . ' more' : ''
            );
        }

        if ($worstPosition) {
            $parts[] = sprintf(
                'The most significant single gap is %s, with %d approved posts but only %d filled.',
                $worstPosition->position->title,
                $worstPosition->approvedAmt,
                $worstPosition->totalInPos
            );
        }

        // Aggregate data-quality mention — count only, never per-row detail.
        // A Director needs to know "something needs review," not a list of
        // every mismatched unit; that level of detail belongs on the
        // operational breakdown page Planning Officer/Super Admin actually
        // work from.
        $unreconciledCount = $rows->filter(fn ($r) => ! $r->reconciles)->count();
        $unboundCount       = $rows->filter(fn ($r) => $r->unboundDataWarnings->isNotEmpty())->count();
        if ($unreconciledCount > 0 || $unboundCount > 0) {
            $dataQualityParts = [];
            if ($unreconciledCount > 0) {
                $dataQualityParts[] = "{$unreconciledCount} position(s) show a mismatch between the ward breakdown and monthly submissions";
            }
            if ($unboundCount > 0) {
                $dataQualityParts[] = "{$unboundCount} position(s) have headcount recorded in units not yet configured for them";
            }
            $parts[] = 'Data quality note: ' . implode('; ', $dataQualityParts)
                . ' — recommend a review with Planning.';
        }

        if (! $calc->hasAllocationData) {
            $parts[] = 'Note: no Unit Post Allocation figures have been entered for this year yet — the counts above are derived from employee profile records only, and may not reflect the most current ward-level placement.';
        }

        return implode(' ', $parts);
    }
}
