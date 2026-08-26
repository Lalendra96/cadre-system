<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Notifies Subject Officers when one of their employees is approaching
 * retirement, at 90/60/30-day marks (fires once per employee per marker,
 * using a cache flag rather than a DB column since this is a computed
 * date — retire_date is derived from date_of_birth + retirement_age, not
 * stored — so there's no natural "notified_at" column to check like
 * EmployeeIncrement has).
 *
 * SCHEDULING — add to app/Console/Kernel.php:
 *   $schedule->command('retirements:notify-upcoming')->dailyAt('07:15');
 */
class NotifyUpcomingRetirements extends Command
{
    protected $signature = 'retirements:notify-upcoming';

    protected $description = 'Notify Subject Officers of employees approaching retirement (90/60/30 days out)';

    private const MARKERS = [90, 60, 30];

    public function handle(): int
    {
        $employees = Employee::active()->whereNotNull('date_of_birth')->with('subjectCode')->get();
        $notified  = 0;

        foreach ($employees as $employee) {
            $monthsLeft = $employee->months_to_retirement;
            if ($monthsLeft === null) {
                continue;
            }
            $daysLeft = (int) now()->diffInDays($employee->retire_date, false);

            foreach (self::MARKERS as $marker) {
                if ($daysLeft !== $marker) {
                    continue;
                }

                // Idempotency: cache flag per employee+marker, valid well past
                // the marker day so a re-run today never double-sends.
                $cacheKey = "retirement-notified:{$employee->id}:{$marker}";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                if (! $employee->subject_code_id) {
                    continue;
                }

                $officers = User::active()
                    ->whereHas('subjectCodes', fn ($q) => $q->where('subject_codes.id', $employee->subject_code_id))
                    ->get();

                if ($officers->isEmpty()) {
                    continue;
                }

                NotificationService::sendToMany(
                    $officers,
                    type:  'retirement_upcoming',
                    title: 'Employee approaching retirement',
                    body:  "{$employee->display_name} retires on {$employee->retire_date->format('d M Y')} — {$marker} days from now.",
                    link:  route('reports.retirement-projections'),
                );

                Cache::put($cacheKey, true, now()->addDays(7));
                $notified++;
            }
        }

        $this->info("Sent {$notified} retirement reminder(s).");
        return self::SUCCESS;
    }
}
