<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AdministrativeIntelligenceService;
use App\Services\OfficialReportingService;
use Illuminate\Console\Command;

class GenerateOfficialWorkforceReport extends Command
{
    protected $signature = 'reports:generate-official {--period= : Period key, defaults to current month}';

    protected $description = 'Prepare the monthly official workforce report for independent checking';

    public function handle(): int
    {
        $actor = User::query()
            ->where('is_active', true)
            ->get()
            ->first(fn (User $user): bool => $user->isPlanningOfficer() || $user->isSuperAdmin());
        if ($actor === null) {
            return self::SUCCESS;
        }
        $period = $this->option('period') ?: now()->format('Y-m');
        OfficialReportingService::prepare(
            'workforce_monthly',
            $period,
            AdministrativeIntelligenceService::summary((int) now()->year, 12, 0, 0),
            $actor,
        );
        $this->info("Prepared official workforce report for {$period}.");

        return self::SUCCESS;
    }
}
