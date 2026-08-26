<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Central place to write audit_logs rows. Keep this the ONLY writer of that
 * table so the format stays consistent everywhere it's called from.
 *
 * Usage:
 *   AuditLogService::created($model, "Submitted March 2026 entry for SC-014");
 *   AuditLogService::updated($model, $oldAttributes, "Updated April 2026 entry");
 *   AuditLogService::deleted($model);
 */
class AuditLogService
{
    public static function created(Model $model, ?string $description = null): void
    {
        self::write('created', $model, [], $model->getAttributes(), $description);
    }

    /**
     * @param array $oldAttributes Attributes captured BEFORE the update was applied
     *                              (e.g. $model->getOriginal() taken prior to ->update()).
     */
    public static function updated(Model $model, array $oldAttributes, ?string $description = null): void
    {
        $new = array_intersect_key($model->getAttributes(), $oldAttributes);
        $old = array_intersect_key($oldAttributes, $new);

        // Only store fields that actually changed, to keep logs compact and readable.
        $changedKeys = array_keys(array_diff_assoc(
            array_map('strval', $new),
            array_map('strval', $old)
        ));

        $old = array_intersect_key($old, array_flip($changedKeys));
        $new = array_intersect_key($new, array_flip($changedKeys));

        if (empty($changedKeys)) {
            return; // nothing actually changed — don't log a no-op
        }

        self::write('updated', $model, $old, $new, $description);
    }

    public static function deleted(Model $model, ?string $description = null): void
    {
        self::write('deleted', $model, $model->getAttributes(), [], $description);
    }

    private static function write(string $action, Model $model, array $old, array $new, ?string $description): void
    {
        // Never log sensitive fields, even if they were present on the model.
        foreach (['password', 'remember_token'] as $sensitive) {
            unset($old[$sensitive], $new[$sensitive]);
        }

        AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => $action,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->getKey(),
            'description'    => $description,
            'old_values'     => empty($old) ? null : $old,
            'new_values'     => empty($new) ? null : $new,
            'ip_address'     => Request::ip(),
            'created_at'     => now(),
        ]);
    }

// ── Security 18: Export / Download Audit ───────────────────────────────

    /**
     * Log a data export or file download event.
     * Call this from every LetterController::downloadAttachment(),
     * ReportController export, and CSV/Excel download.
     */
    public static function logExport(
        int    $userId,
        string $exportType,
        string $filename,
        string $resourceType = null,
        int    $resourceId   = null,
        int    $fileSizeBytes = null,
        string $ip           = null,
        string $userAgent    = null,
    ): void {
        if (! \App\Models\SystemSetting::getBool('export_audit_enabled', true)) {
            return;
        }

        \App\Models\ExportAuditLog::create([
            'user_id'         => $userId,
            'export_type'     => $exportType,
            'resource_type'   => $resourceType,
            'resource_id'     => $resourceId,
            'filename'        => $filename,
            'file_size_bytes' => $fileSizeBytes,
            'ip_address'      => $ip,
            'user_agent'      => $userAgent ? substr($userAgent, 0, 255) : null,
            'exported_at'     => now(),
        ]);
    }
}
