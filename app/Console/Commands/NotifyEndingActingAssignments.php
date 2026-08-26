<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ActingSubjectOfficer;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Notifies both the acting officer AND Super Admin 7 days before a
 * time-boxed acting Subject Officer assignment's end_date — so access
 * isn't unexpectedly cut off mid-task, and Super Admin knows to extend
 * or hand over before it lapses. Open-ended assignments (end_date IS
 * NULL) are never flagged — nothing to expire.
 *
 * SCHEDULING — add to app/Console/Kernel.php:
 *   $schedule->command('acting-officers:notify-ending')->dailyAt('07:30');
 */
class NotifyEndingActingAssignments extends Command
{
    protected $signature = 'acting-officers:notify-ending {--days=7 : Days before end_date to warn}';

    protected $description = 'Notify acting Subject Officers and Super Admin before a time-boxed assignment ends';

    public function handle(): int
    {
        $daysAhead = (int) $this->option('days');

        $assignments = ActingSubjectOfficer::currentlyEffective()
            ->whereNotNull('end_date')
            ->whereDate('end_date', now()->addDays($daysAhead)->toDateString())
            ->with(['user', 'subjectCode', 'appointedBy'])
            ->get();

        if ($assignments->isEmpty()) {
            $this->info('No acting Subject Officer assignments ending soon.');
            return self::SUCCESS;
        }

        foreach ($assignments as $a) {
            NotificationService::send(
                $a->user,
                type:  'acting_assignment_ending',
                title: 'Your acting Subject Officer access is ending soon',
                body:  "Your access to subject code {$a->subjectCode->code} ends on {$a->end_date->format('d M Y')}.",
                link:  route('dashboard'),
            );

            $superAdmins = \App\Models\User::active()->havingRole(\App\Models\User::ROLE_SUPER_ADMIN)->get();
            NotificationService::sendToMany(
                $superAdmins,
                type:  'acting_assignment_ending',
                title: 'Acting Subject Officer assignment ending soon',
                body:  "{$a->user->name}'s acting appointment for {$a->subjectCode->code} ends {$a->end_date->format('d M Y')} — extend or hand over if still needed.",
                link:  route('acting-subject-officers.index'),
            );
        }

        $this->info("Notified for {$assignments->count()} ending assignment(s).");
        return self::SUCCESS;
    }
}
