<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\ServiceLetterTemplate;

/**
 * Renders a ServiceLetterTemplate's {{placeholder}} body against a
 * specific employee, producing the final rendered_body stored on
 * ServiceLetter.
 *
 * SECURITY — this is a plain find/replace substitution, NOT a template
 * engine (no eval, no Blade compilation of user-editable text). Every
 * substituted value is passed through e() (htmlspecialchars) so an
 * employee's name or notes field containing "<script>" or similar can
 * never inject markup into a rendered letter that other officers will
 * later view or print. Unrecognised {{tokens}} in the template body are
 * left as-is (not silently blanked) so a typo in a template is visible
 * to whoever drafts a letter from it, rather than disappearing.
 */
class ServiceLetterService
{
    public const PLACEHOLDERS = [
        '{{employee_name}}'           => "Employee's full display name (with salutation)",
        '{{pay_no}}'                  => "Employee's pay number",
        '{{nic_number}}'              => "Employee's NIC number (full — appropriate for an official signed document)",
        '{{position_title}}'          => 'Current position / designation',
        '{{subject_code}}'            => 'Subject code the employee belongs to',
        '{{unit_name}}'               => 'Current unit / ward',
        '{{date_of_appointment}}'     => 'Date of appointment (d M Y)',
        '{{date_reported_for_duty}}'  => 'Date reported for duty at this hospital (d M Y)',
        '{{salary_scale}}'            => "Employee's current salary scale code",
        '{{current_grade}}'           => "Employee's current grade, if recorded",
        '{{today}}'                   => "Today's date (d M Y)",
        '{{hospital_name}}'           => 'Fixed: "Teaching Hospital Peradeniya"',
    ];

    public static function render(ServiceLetterTemplate $template, Employee $employee): string
    {
        $values = [
            '{{employee_name}}'          => $employee->display_name,
            '{{pay_no}}'                 => $employee->pay_no ?? '—',
            '{{nic_number}}'             => $employee->nic_number ?? '—',
            '{{position_title}}'         => $employee->position?->title ?? '—',
            '{{subject_code}}'           => $employee->subjectCode?->code ?? '—',
            '{{unit_name}}'              => $employee->unit?->name ?? '—',
            '{{date_of_appointment}}'    => $employee->date_of_appointment?->format('d M Y') ?? '—',
            '{{date_reported_for_duty}}' => $employee->date_reported_for_duty?->format('d M Y') ?? '—',
            '{{salary_scale}}'           => $employee->salaryScale?->code ?? '—',
            '{{current_grade}}'          => $employee->current_grade?->positionGrade?->name ?? '—',
            '{{today}}'                  => now()->format('d M Y'),
            '{{hospital_name}}'          => 'Teaching Hospital Peradeniya',
        ];

        // Escape values (never the template body itself) so an employee's
        // free-text field can't inject markup into the rendered letter.
        $escaped = array_map(fn ($v) => e((string) $v), $values);

        return strtr($template->body, $escaped);
    }
}
