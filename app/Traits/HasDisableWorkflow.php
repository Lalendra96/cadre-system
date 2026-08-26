<?php

namespace App\Traits;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Builder;

/**
 * HasDisableWorkflow
 *
 * Provides a consistent disable / re-enable pattern for any Eloquent model.
 *
 * REQUIRED columns on the model's table:
 *   is_active       BOOLEAN  DEFAULT TRUE   NOT NULL
 *   disabled_by     BIGINT   NULL  FK → users(id) ON DELETE SET NULL
 *   disabled_at     TIMESTAMP NULL
 *   disable_reason  TEXT      NULL  (stores mandatory reason on disable)
 *
 * USAGE IN CONTROLLERS
 *
 *   // Disable
 *   if (! $model->disableRecord(auth()->id(), $request->input('disable_reason'))) {
 *       return back()->with('error', 'Could not disable record.');
 *   }
 *
 *   // Re-enable
 *   $model->enableRecord(auth()->id());
 *
 *   // In queries — active only (use in regular index pages):
 *   Model::active()->get();
 *
 *   // With disabled records (Director / Super Admin management views):
 *   Model::withDisabled()->get();
 *
 * DATA SECURITY NOTE
 * By convention ALL controllers must call ->active() when fetching records
 * for normal operations. Disabled records must only appear on dedicated
 * management views behind elevated-role middleware.
 */
trait HasDisableWorkflow
{
    // ── Relationship ──────────────────────────────────────────────────────

    public function disabledByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'disabled_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    /** Returns only active (non-disabled) records. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->getTable() . '.is_active', true);
    }

    /** Returns ALL records including disabled ones (management views only). */
    public function scopeWithDisabled(Builder $query): Builder
    {
        return $query; // no filter — returns everything
    }

    /** Returns only disabled records. */
    public function scopeOnlyDisabled(Builder $query): Builder
    {
        return $query->where($this->getTable() . '.is_active', false);
    }

    // ── Actions ────────────────────────────────────────────────────────────

    /**
     * Disable this record.
     *
     * @param  int         $userId   The ID of the user performing the action.
     * @param  string|null $reason   Human-readable reason (mandatory for audit).
     * @return bool
     */
    public function disableRecord(int $userId, ?string $reason = null): bool
    {
        $old = $this->getOriginal();

        $updated = $this->update([
            'is_active'      => false,
            'disabled_by'    => $userId,
            'disabled_at'    => now(),
            'disable_reason' => $reason,
        ]);

        if ($updated) {
            AuditLogService::updated(
                $this,
                $old,
                sprintf(
                    '[DISABLED] %s #%s by user #%d — Reason: %s',
                    class_basename($this),
                    $this->getKey(),
                    $userId,
                    $reason ?? '(no reason provided)'
                )
            );
        }

        return $updated;
    }

    /**
     * Re-enable a previously disabled record.
     *
     * @param  int $userId  The ID of the user performing the re-enable.
     * @return bool
     */
    public function enableRecord(int $userId): bool
    {
        $old = $this->getOriginal();

        $updated = $this->update([
            'is_active'      => true,
            'disabled_by'    => null,
            'disabled_at'    => null,
            'disable_reason' => null,
        ]);

        if ($updated) {
            AuditLogService::updated(
                $this,
                $old,
                sprintf(
                    '[RE-ENABLED] %s #%s by user #%d',
                    class_basename($this),
                    $this->getKey(),
                    $userId
                )
            );
        }

        return $updated;
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isDisabled(): bool
    {
        return ! $this->is_active;
    }

    /**
     * Returns a human-friendly summary of who disabled the record and when.
     * Useful in view tooltips / blade templates.
     */
    public function disableSummary(): string
    {
        if ($this->is_active) {
            return 'Active';
        }

        $by   = $this->disabledByUser?->name ?? 'Unknown';
        $at   = $this->disabled_at?->format('d M Y H:i') ?? '—';
        $why  = $this->disable_reason ?? 'No reason provided';

        return "Disabled by {$by} on {$at}: {$why}";
    }
}
