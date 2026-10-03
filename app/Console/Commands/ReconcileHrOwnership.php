<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\HrIntelligenceService;
use Illuminate\Console\Command;

class ReconcileHrOwnership extends Command
{
    protected $signature = 'hr:reconcile-ownership';

    protected $description = 'Record HR ownership changes, raise coverage alerts and queue open work for reassignment';

    public function handle(): int
    {
        $result = HrIntelligenceService::synchronize();
        $this->info($result['changed'].' employee ownership observations recorded.');

        return self::SUCCESS;
    }
}
