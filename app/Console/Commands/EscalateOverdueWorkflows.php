<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\HrEscalation;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class EscalateOverdueWorkflows extends Command
{
    protected $signature = 'hr:escalate-overdue';

    protected $description = 'Escalate open workflow records whose due date has passed';

    public function handle(): int
    {
        $manager = User::query()
            ->where('is_active', true)
            ->get()
            ->first(
                fn (User $user): bool => $user->isSuperAdmin() || $user->isPlanningOfficer() || $user->isAdminGroup(),
            );
        if ($manager === null) {
            return self::SUCCESS;
        }
        $count = 0;
        HrEscalation::query()
            ->where('status', 'open')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get()
            ->each(function (HrEscalation $escalation) use ($manager, &$count): void {
                $escalation->update(['severity' => 'critical']);
                NotificationService::send(
                    $manager,
                    'hr_escalation',
                    'Overdue HR workflow',
                    'An assigned HR workflow is overdue and needs management attention.',
                    route('hr-intelligence.index'),
                );
                $count++;
            });
        $this->info("Escalated {$count} overdue workflow(s).");

        return self::SUCCESS;
    }
}
