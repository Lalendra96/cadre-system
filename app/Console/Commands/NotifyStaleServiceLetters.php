<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ServiceLetter;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Reminds the Administrative Officer(s) about Service Letters that have
 * sat in pending_approval for longer than the threshold, so a drafted
 * letter doesn't silently stall in the approval queue. Fires once per
 * letter per week (cache-flagged) rather than daily, to avoid notification
 * fatigue on an already-known backlog item.
 *
 * SCHEDULING — add to app/Console/Kernel.php:
 *   $schedule->command('service-letters:notify-stale')->dailyAt('08:00');
 */
class NotifyStaleServiceLetters extends Command
{
    protected $signature = 'service-letters:notify-stale {--days=3 : Days pending before considered stale}';

    protected $description = 'Remind the Administrative Officer of service letters pending approval too long';

    public function handle(): int
    {
        $daysThreshold = (int) $this->option('days');

        $stale = ServiceLetter::pendingApproval()
            ->active()
            ->where('updated_at', '<=', now()->subDays($daysThreshold))
            ->with('employee')
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No stale service letters pending approval.');
            return self::SUCCESS;
        }

        $aoUsers = User::active()
            ->whereHas('category', fn ($q) => $q->where('name', 'Administrative Officer / Hospital Secretary'))
            ->get();

        if ($aoUsers->isEmpty()) {
            $this->warn('No Administrative Officer account found to notify.');
            return self::SUCCESS;
        }

        $reminded = 0;
        foreach ($stale as $letter) {
            $cacheKey = "stale-service-letter-notified:{$letter->id}:" . now()->format('oW'); // once per ISO week
            if (Cache::has($cacheKey)) {
                continue;
            }

            $daysPending = (int) $letter->updated_at->diffInDays(now());

            NotificationService::sendToMany(
                $aoUsers,
                type:  'service_letter_stale',
                title: 'Service letter awaiting approval too long',
                body:  "\"{$letter->subject}\" for {$letter->employee->display_name} has been pending {$daysPending} day(s).",
                link:  route('service-letters.index', ['status' => 'pending_approval']),
            );

            Cache::put($cacheKey, true, now()->addWeek());
            $reminded++;
        }

        $this->info("Reminded on {$reminded} stale service letter(s).");
        return self::SUCCESS;
    }
}
