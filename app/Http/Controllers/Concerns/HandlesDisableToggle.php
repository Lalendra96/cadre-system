<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * HandlesDisableToggle
 *
 * Reusable controller concern that provides a single, safe, audited
 * implementation of the disable / re-enable workflow.
 *
 * Every controller that manages a model using HasDisableWorkflow should
 * use this trait instead of writing its own toggle() method.
 *
 * USAGE IN CONTROLLER
 * ───────────────────
 *
 *   use HandlesDisableToggle;
 *
 *   public function toggle(Request $request, Position $position): RedirectResponse
 *   {
 *       // Optional: add extra guards before delegating
 *       return $this->performToggle(
 *           request:      $request,
 *           model:        $position,
 *           label:        "Position \"{$position->title}\"",
 *           requireReason:true,
 *       );
 *   }
 *
 * WHAT IT DOES
 * ─────────────────────────────────────────────────────────────────────
 * Disable:
 *   1. Validates disable_reason (required, min 10 chars, max 500) when
 *      $requireReason is true.
 *   2. Calls $model->disableRecord() from HasDisableWorkflow trait.
 *   3. Returns back() with a human-readable success message.
 *
 * Re-enable:
 *   1. Calls $model->enableRecord().
 *   2. Returns back() with a human-readable success message.
 *
 * Both operations are already audit-logged inside HasDisableWorkflow.
 *
 * DATA SECURITY NOTE
 * ──────────────────
 * This concern does NOT handle authorization — that MUST happen in the
 * calling controller method (or via route middleware) before delegating
 * here. The concern assumes the caller has already verified the user has
 * the right to disable/enable the given model instance.
 */
trait HandlesDisableToggle
{
    /**
     * Perform a disable or re-enable toggle on any HasDisableWorkflow model.
     *
     * @param  Request  $request
     * @param  Model    $model        Any Eloquent model using HasDisableWorkflow
     * @param  string   $label        Human-readable name shown in flash messages
     *                                (e.g. 'Position "Staff Nurse"')
     * @param  bool     $requireReason Whether a written reason is mandatory
     * @return RedirectResponse
     *
     * @throws ValidationException   When disable_reason fails validation
     * @throws \RuntimeException     When the model does not use HasDisableWorkflow
     */
    protected function performToggle(
        Request $request,
        Model   $model,
        string  $label,
        bool    $requireReason = true,
    ): RedirectResponse {

        // Guard: ensure the model actually supports the disable workflow
        if (! method_exists($model, 'disableRecord')) {
            throw new \RuntimeException(
                sprintf(
                    '%s::performToggle() requires the model (%s) to use the HasDisableWorkflow trait.',
                    static::class,
                    get_class($model)
                )
            );
        }

        $isActive = (bool) $model->getAttribute('is_active');

        if ($isActive) {
            // ── Disable path ─────────────────────────────────────────────────

            if ($requireReason) {
                $request->validate(
                    ['disable_reason' => ['required', 'string', 'min:10', 'max:500']],
                    [
                        'disable_reason.required' => 'A written reason is required before disabling a record.',
                        'disable_reason.min'      => 'The reason must be at least 10 characters — please describe why this record is being disabled.',
                        'disable_reason.max'      => 'The reason may not exceed 500 characters.',
                    ]
                );
            }

            $reason = filled($request->input('disable_reason'))
                ? trim($request->input('disable_reason'))
                : null;

            $model->disableRecord($request->user()->id, $reason);

            return back()->with(
                'success',
                "{$label} has been disabled and will no longer appear in active lists or reports. "
                . "The record is preserved and can be re-enabled at any time."
            );
        }

        // ── Re-enable path ───────────────────────────────────────────────────

        $model->enableRecord($request->user()->id);

        return back()->with(
            'success',
            "{$label} has been re-enabled and will appear in active lists again."
        );
    }
}
