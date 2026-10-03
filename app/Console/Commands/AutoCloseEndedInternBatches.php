<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\InternBatchLifecycleService;
use Illuminate\Console\Command;

class AutoCloseEndedInternBatches extends Command
{
    protected $signature = 'intern-batches:auto-close-ended';

    protected $description = 'Automatically close active intern batches after their official end date has passed.';

    public function handle(): int
    {
        $count = InternBatchLifecycleService::autoCloseEndedBatches();
        $this->info("Auto-closed {$count} ended intern batch(es).");

        return self::SUCCESS;
    }
}
