<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InternBatch;
use Illuminate\Support\Carbon;

final class InternBatchLifecycleService
{
    /**
     * Close any active batch whose official end date has already passed.
     *
     * This is intentionally idempotent and may be called by both the
     * scheduler and read-side screens. It never deletes allocation data.
     */
    public static function autoCloseEndedBatches(): int
    {
        $today = Carbon::today();
        $closed = 0;

        InternBatch::query()
            ->where('is_active', true)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($batches) use (&$closed): void {
                foreach ($batches as $batch) {
                    $old = $batch->getOriginal();
                    $batch->forceFill([
                        'is_active' => false,
                        'closed_at' => now(),
                        'closed_by' => null,
                        'close_reason' => 'Automatically closed because the official batch end date has passed.',
                        'closed_automatically' => true,
                    ])->save();

                    AuditLogService::updated(
                        $batch,
                        $old,
                        'Intern batch automatically closed after its official end date passed.',
                    );
                    $closed++;
                }
            });

        return $closed;
    }
}
