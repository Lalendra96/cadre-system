<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Registers every scheduled command this project defines. Each command's
 * own docblock already specifies its intended schedule (added when that
 * command was built) — this file exists to actually WIRE those up, since
 * none of them run automatically without being registered here.
 *
 * Before this file existed, every command below was fully built and
 * correct but could only ever fire manually via `php artisan <command>` —
 * none of the daily reminders (increments, retirements, acting
 * assignments, vacancy letters, grade eligibility, service confirmation
 * milestones, stale service letters) or the housekeeping purge job had
 * ever actually run on their own.
 *
 * REQUIRES: the server's crontab must run Laravel's scheduler every
 * minute for any of this to take effect —
 *   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
 * Without that single cron entry, registering commands here still does
 * nothing; this file and that cron entry are two separate requirements,
 * both necessary.
 */
class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('hr:reconcile-ownership')->everyFiveMinutes()->withoutOverlapping(15);
        $schedule->command('increments:notify-upcoming')->dailyAt('07:00');
        $schedule->command('retirements:notify-upcoming')->dailyAt('07:15');
        $schedule->command('employees:notify-confirmation-milestone')->dailyAt('07:20');
        $schedule->command('acting-officers:notify-ending')->dailyAt('07:30');
        $schedule->command('employees:notify-grade-promotion-eligibility')->dailyAt('07:35');
        $schedule->command('vacancy-letters:notify-expiring')->dailyAt('07:45');
        $schedule->command('service-letters:notify-stale')->dailyAt('08:00');
        $schedule->command('letters:purge-expired')->daily();
        $schedule->command('intern-batches:auto-close-ended')->dailyAt('00:10')->withoutOverlapping(10);
        $schedule->command('documents:notify-expiring')->dailyAt('07:25');
        $schedule->command('hr:escalate-overdue')->hourly()->withoutOverlapping(10);
        $schedule->command('reports:generate-official')->monthlyOn(1, '06:00')->withoutOverlapping(30);
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
