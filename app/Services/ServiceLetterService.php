<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\ServiceLetterTemplate;

class ServiceLetterService
{
    public const PLACEHOLDERS = [
        '{{employee_name}}' => "Employee's full display name (with salutation)",
        '{{pay_no}}' => "Employee's pay number",
        '{{nic_number}}' => "Employee's NIC number (full — appropriate for an official signed document)",
        '{{position_title}}' => 'Current position / designation',
        '{{subject_code}}' => 'Subject code the employee belongs to',
        '{{unit_name}}' => 'Current unit / ward',
        '{{date_of_appointment}}' => 'Date of appointment (d M Y)',
        '{{date_reported_for_duty}}' => 'Date reported for duty at this hospital (d M Y)',
        '{{salary_scale}}' => "Employee's current salary scale code",
        '{{current_grade}}' => "Employee's current grade, if recorded",
        '{{today}}' => "Today's date (d M Y)",
        '{{hospital_name}}' => 'Fixed: "Teaching Hospital Peradeniya"',
        '{{date_joined_public_service}}' => 'Date joined public service',
        '{{date_joined_combined_service}}' => 'Date joined combined service',
        '{{combined_service_name}}' => 'Combined service name',
        '{{current_grade_since}}' => 'Current grade effective date',
        '{{service_file_no}}' => 'Service file number',
        '{{professional_registration_no}}' => 'Professional registration number',
    ];

    public static function render(
        ServiceLetterTemplate $template,
        Employee $employee
    ): string {
        $values = [
            '{{employee_name}}' => $employee->display_name,
            '{{pay_no}}' => $employee->pay_no ?? '—',
            '{{nic_number}}' => $employee->nic_number ?? '—',
            '{{position_title}}' => $employee->position?->title ?? '—',
            '{{subject_code}}' => $employee->subjectCode?->code ?? '—',
            '{{unit_name}}' => $employee->unit?->name ?? '—',
            '{{date_of_appointment}}' => $employee->date_of_appointment?->format('d M Y') ?? '—',
            '{{date_reported_for_duty}}' => $employee->date_reported_for_duty?->format('d M Y') ?? '—',
            '{{salary_scale}}' => $employee->salaryScale?->code ?? '—',
            '{{current_grade}}' => $employee->current_grade?->positionGrade?->name ?? '—',
            '{{today}}' => now()->format('d M Y'),
            '{{hospital_name}}' => 'Teaching Hospital Peradeniya',
            '{{date_joined_public_service}}' => $employee->date_joined_public_service?->format('d M Y') ?? '—',
            '{{date_joined_combined_service}}' => $employee->date_joined_combined_service?->format('d M Y') ?? '—',
            '{{combined_service_name}}' => $employee->combined_service_name ?? '—',
            '{{current_grade_since}}' => $employee->current_grade?->effective_date?->format('d M Y')
                ?? $employee->date_current_grade?->format('d M Y')
                ?? '—',
            '{{service_file_no}}' => $employee->service_file_no ?? '—',
            '{{professional_registration_no}}' => $employee->professional_registration_no ?? '—',
        ];

        return strtr(
            $template->body,
            array_map(
                static fn ($value) => e((string) $value),
                $values
            )
        );
    }
}
