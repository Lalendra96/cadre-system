<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\EmployeeIncrement;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Sends a one-time in-app notification 30 days before an employee's
 * upcoming increment_date, to the Subject Officer(s) responsible for that
 * employee's subject code (permanent assignment — acting officers are
 * intentionally NOT notified here, since an acting assignment may lapse
 * before the increment date and the notification would be misleading).
 *
 * IDEMPOTENT: relies on EmployeeIncrement.notified_at being set after a
 * successful send, so re-running this command (or a missed day being
 * caught up later) never double-notifies the same increment.
 *
 * SCHEDULING — add to app/Console/Kernel.php:
 *   $schedule->command('increments:notify-upcoming')->dailyAt('07:00');
 */
class NotifyUpcomingIncrements extends Command
{
    protected $signature = 'increments:notify-upcoming {--days=30 : How many days ahead to check}';

    protected $description = 'Notify Subject Officers of employee increments due within N days (default 30)';

    public function handle(): int
    {
        $daysAhead = (int) $this->option('days');

        $increments = EmployeeIncrement::dueForReminder($daysAhead)
            ->with('employee.subjectCode')
            ->get();

        if ($increments->isEmpty()) {
            $this->info('No upcoming increments due for a reminder.');
            return self::SUCCESS;
        }

        $notified = 0;

        foreach ($increments as $increment) {
            $employee = $increment->employee;
            if (! $employee || ! $employee->subject_code_id) {
                continue;
            }

            $officers = User::active()
                ->whereHas('subjectCodes', fn ($q) => $q->where('subject_codes.id', $employee->subject_code_id))
                ->get();

            if ($officers->isEmpty()) {
                $this->warn("Increment #{$increment->id}: no Subject Officer assigned to subject code #{$employee->subject_code_id} — skipped.");
                continue;
            }

            NotificationService::sendToMany(
                $officers,
                type:  'increment_upcoming',
                title: 'Upcoming increment due',
                body:  "{$employee->display_name}'s increment is due on {$increment->increment_date->format('d M Y')} "
                     . "(in {$this->daysUntil($increment)} day(s)).",
                link:  route('employee-increments.index', $employee),
            );

            $increment->update(['notified_at' => now()]);
            $notified++;
        }

        $this->info("Notified for {$notified} upcoming increment(s).");
        return self::SUCCESS;
    }

    private function daysUntil(EmployeeIncrement $increment): int
    {
        return (int) now()->diffInDays($increment->increment_date, false);
    }
}
