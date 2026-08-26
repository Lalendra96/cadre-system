<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Notifies the Subject Officer when an employee reaches exactly 5 years
 * since their date_confirmed (Confirmation-in-Service) — a real Sri
 * Lanka public service milestone tied to pension and other service-length
 * entitlements.
 *
 * Fires once per employee on the day the 5-year mark falls (cache-flagged
 * for idempotency, same pattern as NotifyUpcomingRetirements — there's no
 * stored "notified_at" column for this, since it's a computed date, not a
 * recorded event with its own row).
 *
 * SCHEDULING — add to app/Console/Kernel.php:
 *   $schedule->command('employees:notify-confirmation-milestone')->dailyAt('07:20');
 */
class NotifyServiceConfirmationMilestone extends Command
{
    protected $signature = 'employees:notify-confirmation-milestone {--years=5 : Years of confirmed service to notify at}';

    protected $description = 'Notify Subject Officers when an employee reaches N years since confirmation-in-service (default 5)';

    public function handle(): int
    {
        $years = (int) $this->option('years');

        $employees = Employee::active()
            ->where('is_confirmed', true)
            ->whereNotNull('date_confirmed')
            ->whereDate('date_confirmed', now()->subYears($years)->toDateString())
            ->with('subjectCode')
            ->get();

        if ($employees->isEmpty()) {
            $this->info("No employees reaching {$years} years since confirmation today.");
            return self::SUCCESS;
        }

        $notified = 0;

        foreach ($employees as $employee) {
            $cacheKey = "confirmation-milestone-notified:{$employee->id}:{$years}";
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
                $this->warn("Employee #{$employee->id}: no Subject Officer assigned to subject code #{$employee->subject_code_id} — skipped.");
                continue;
            }

            NotificationService::sendToMany(
                $officers,
                type:  'confirmation_milestone',
                title: "{$years}-year confirmed-service milestone",
                body:  "{$employee->display_name} has completed {$years} years since confirmation in service "
                     . "({$employee->date_confirmed->format('d M Y')}).",
                link:  route('employee-confirmation.edit', $employee),
            );

            Cache::put($cacheKey, true, now()->addDays(2));
            $notified++;
        }

        $this->info("Notified for {$notified} employee(s) reaching the {$years}-year confirmation milestone.");
        return self::SUCCESS;
    }
}
