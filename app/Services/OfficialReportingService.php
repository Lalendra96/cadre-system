<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OfficialReport;
use App\Models\User;
use App\Models\WorkforceSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OfficialReportingService
{
    public static function prepare(string $type, string $period, array $payload, User $actor): OfficialReport
    {
        abort_unless(
            $actor->is_active && ($actor->isSuperAdmin() || $actor->isPlanningOfficer() || $actor->isAdminGroup()),
            403,
        );

        return OfficialReport::updateOrCreate(
            ['report_type' => $type, 'period_key' => $period],
            [
                'status' => 'prepared',
                'payload' => $payload,
                'prepared_by' => $actor->id,
                'checked_by' => null,
                'approved_by' => null,
            ],
        );
    }

    public static function advance(OfficialReport $report, User $actor, string $status): void
    {
        abort_unless(
            $actor->is_active && ($actor->isSuperAdmin() || $actor->isPlanningOfficer() || $actor->isAdminGroup()),
            403,
        );
        if (
            ($status === 'checked' && $report->prepared_by === $actor->id) ||
            ($status === 'approved' && $report->checked_by === $actor->id)
        ) {
            throw ValidationException::withMessages([
                'status' => 'The prepared or checked officer cannot perform the next approval step.',
            ]);
        }
        if (
            ($report->status === 'prepared' && $status !== 'checked') ||
            ($report->status === 'checked' && $status !== 'approved')
        ) {
            throw ValidationException::withMessages(['status' => 'Report approvals must be sequential.']);
        }
        $report->update([
            $status === 'checked' ? 'checked_by' : 'approved_by' => $actor->id,
            $status === 'checked' ? 'checked_at' : 'approved_at' => now(),
            'status' => $status,
        ]);
    }

    public static function sign(OfficialReport $report, User $actor): WorkforceSnapshot
    {
        abort_unless($report->status === 'approved' && $report->approved_by !== $actor->id, 403);

        return DB::transaction(function () use ($report, $actor): WorkforceSnapshot {
            $payload = $report->payload;
            $hash = hash('sha512', json_encode($payload, JSON_THROW_ON_ERROR));
            $report->update(['signature_hash' => $hash]);

            return WorkforceSnapshot::create([
                'official_report_id' => $report->id,
                'payload' => $payload,
                'content_hash' => $hash,
                'signed_by' => $actor->id,
                'signed_at' => now(),
                'archived_at' => now(),
            ]);
        });
    }
}
