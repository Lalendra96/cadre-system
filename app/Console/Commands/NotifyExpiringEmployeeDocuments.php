<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\SystemSetting;
use App\Services\HrReminderService;
use App\Services\HrResponsibilityService;
use Illuminate\Console\Command;

class NotifyExpiringEmployeeDocuments extends Command
{
    protected $signature = 'documents:notify-expiring {--days=60 : Days ahead to check}';

    protected $description = 'Notify HR position owners about expiring verified employee documents and registrations';

    public function handle(): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);
        if ($days === false || $days < 0 || $days > 3650) {
            $this->error('Days must be an integer from 0 to 3650.');

            return self::FAILURE;
        }
        $markers = HrReminderService::markers(
            $days !== 60 ? [$days] : (array) SystemSetting::getJson('document_expiry_reminder_days', [60, 30, 7, 0]),
        );
        $recipients = HrResponsibilityService::recipientMap();
        $sent = 0;
        EmployeeDocument::query()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', today()->addDays(max($markers)))
            ->where('verification_status', 'verified')
            ->whereHas('employee', fn ($q) => $q->where('is_active', true))
            ->with('employee')
            ->chunkById(200, function ($documents) use ($markers, $recipients, &$sent) {
                foreach ($documents as $document) {
                    $daysLeft = today()->diffInDays($document->expiry_date->startOfDay(), false);
                    $marker = HrReminderService::markerFor($daysLeft, $markers);
                    if ($marker === null) {
                        continue;
                    }
                    foreach ($recipients->get($document->employee->position_id, collect()) as $officer) {
                        $sent += HrReminderService::deliver(
                            $officer,
                            'document:'.$document->id.':'.$document->expiry_date->toDateString().':'.$marker,
                            'document_expiry',
                            'Employee document expiry reminder',
                            $document->employee->display_name.
                                ' has a document or registration expiring on '.
                                $document->expiry_date->format('d M Y').
                                '.',
                            route('employee-documents.index', $document->employee),
                        )
                            ? 1
                            : 0;
                    }
                }
            });
        Employee::query()
            ->where('is_active', true)
            ->whereNotNull('professional_registration_expiry')
            ->where('professional_registration_expiry', '<=', today()->addDays(max($markers)))
            ->chunkById(200, function ($employees) use ($markers, $recipients, &$sent): void {
                foreach ($employees as $employee) {
                    $daysLeft = today()->diffInDays($employee->professional_registration_expiry->startOfDay(), false);
                    $marker = HrReminderService::markerFor($daysLeft, $markers);
                    if ($marker === null) {
                        continue;
                    }
                    foreach ($recipients->get($employee->position_id, collect()) as $officer) {
                        $sent += HrReminderService::deliver(
                            $officer,
                            'registration:'.
                                $employee->id.
                                ':'.
                                $employee->professional_registration_expiry->toDateString().
                                ':'.
                                $marker,
                            'professional_registration_expiry',
                            'Professional registration expiry reminder',
                            $employee->display_name.
                                ' has a professional registration expiring on '.
                                $employee->professional_registration_expiry->format('d M Y').
                                '.',
                            route('employees.show', $employee),
                        )
                            ? 1
                            : 0;
                    }
                }
            });
        $this->info("Sent {$sent} document reminder(s).");

        return self::SUCCESS;
    }
}
