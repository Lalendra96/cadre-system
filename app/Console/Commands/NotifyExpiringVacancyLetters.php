<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VacancyAvailabilityLetter;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Notifies the issuing Admin Group officer 7 days before a Vacancy
 * Availability Letter's 90-day cooldown_until passes — a reminder that
 * if the vacancy is still unfilled, a fresh letter will need to be
 * issued once the window closes (see VacancyAvailabilityLetter model
 * docblock for why cooldown_until does double duty as both the
 * re-issue block AND the "currently available" signal).
 *
 * SCHEDULING — add to app/Console/Kernel.php:
 *   $schedule->command('vacancy-letters:notify-expiring')->dailyAt('07:45');
 */
class NotifyExpiringVacancyLetters extends Command
{
    protected $signature = 'vacancy-letters:notify-expiring {--days=7 : Days before cooldown_until to warn}';

    protected $description = 'Notify the issuing officer before a vacancy availability letter expires';

    public function handle(): int
    {
        $daysAhead = (int) $this->option('days');

        $letters = VacancyAvailabilityLetter::active()
            ->whereDate('cooldown_until', now()->addDays($daysAhead)->toDateString())
            ->with(['position', 'subjectCode', 'issuedBy'])
            ->get();

        if ($letters->isEmpty()) {
            $this->info('No vacancy availability letters expiring soon.');
            return self::SUCCESS;
        }

        foreach ($letters as $letter) {
            NotificationService::send(
                $letter->issuedBy,
                type:  'vacancy_letter_expiring',
                title: 'Vacancy availability letter expiring soon',
                body:  "The {$letter->vacancy_count}-vacancy declaration for {$letter->position->title} "
                     . "({$letter->subjectCode->code}) expires {$letter->cooldown_until->format('d M Y')}. "
                     . "If still unfilled, a new letter can be issued once it lapses.",
                link:  route('vacancy-availability-letters.index'),
            );
        }

        $this->info("Notified for {$letters->count()} expiring vacancy letter(s).");
        return self::SUCCESS;
    }
}
