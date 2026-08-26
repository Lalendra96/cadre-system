<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates employee profile create / update.
 *
 * AUTHORIZATION
 * ─────────────
 * Super Admin and Planning Officer: any subject code.
 * Subject Officer: only subject codes assigned to their account.
 * The same scope check is re-enforced in EmployeeController::authorizeOwnEmployee()
 * to ensure both the FormRequest layer and the controller layer independently
 * reject unauthorised access — belt-and-braces for sensitive personnel data.
 */
class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user?->isSuperAdmin()
            || $user?->isPlanningOfficer()
            || $user?->isSubjectOfficer();
    }

    public function rules(): array
    {
        /** @var \App\Models\Employee|null $employee */
        $employee   = $this->route('employee');
        $employeeId = $employee?->id;
        $user       = $this->user();

        // Subject Officers may only assign a subject code that belongs to them —
        // including a currently-effective acting assignment. Must match
        // EmployeeController::assignableCodes() exactly: that method populates
        // the dropdown using effectiveSubjectCodeIds(), so validating against
        // the narrower subjectCodes() (permanent only) here would silently
        // reject a valid selection made by an acting Subject Officer.
        $subjectCodeRule = ($user->isSuperAdmin() || $user->isPlanningOfficer())
            ? ['integer', 'exists:subject_codes,id']
            : ['integer', Rule::in($user->effectiveSubjectCodeIds()->all())];

        return [
            // Identity
            'salutation'          => ['nullable', 'string', Rule::in(['Mr.','Mrs.','Ms.','Miss','Dr.','Rev.','Prof.','Eng.'])],
            'name'                => ['required', 'string', 'max:150'],
            'pay_no'              => ['nullable', 'string', 'max:50',
                                      Rule::unique('employees', 'pay_no')->ignore($employeeId)],
            'nic_number'          => ['nullable', 'string', 'max:12',
                                      'regex:/^(\d{9}[VvXx]|\d{12})$/',
                                      Rule::unique('employees', 'nic_number')->ignore($employeeId)],
            'wop_number'          => ['nullable', 'string', 'max:20',
                                      Rule::unique('employees', 'wop_number')->ignore($employeeId)],
            'gender'              => ['required', Rule::in(['M','F','O'])],

            // Contact
            'email'               => ['nullable', 'email', 'max:150'],
            'whatsapp_mobile'     => ['nullable', 'string', 'max:20',
                                      'regex:/^[0-9+\-\s\(\)]+$/'],

            // Organisational
            'subject_code_id'     => array_merge(['required'], $subjectCodeRule),
            'position_id'         => ['required', 'integer', 'exists:positions,id'],
            'unit_id'             => ['required', 'integer', 'exists:units,id'],
            'salary_scale_id'     => ['nullable', 'integer', 'exists:salary_scales,id'],

            // Service record
            'date_of_birth'       => ['nullable', 'date', 'before:today',
                                      'after:1940-01-01'],
            'date_of_appointment' => ['nullable', 'date', 'before_or_equal:today',
                                      'after:1980-01-01'],
            'date_reported_for_duty' => ['nullable', 'date', 'before_or_equal:today',
                                      'after:1980-01-01'],
            'retirement_age'      => ['nullable', 'integer', 'min:50', 'max:70'],
            'is_confirmed'        => ['boolean'],
            'date_confirmed'      => ['nullable', 'date', 'before_or_equal:today', 'required_if:is_confirmed,1'],
            'confirmation_reference_no' => ['nullable', 'string', 'max:60'],
            'notes'               => ['nullable', 'string', 'max:1000'],

            // Status
            'is_active'           => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_code_id.in'            => 'You may only create or edit profiles under a subject code assigned to your account.',
            'position_id.required'          => 'Please select the position this employee holds.',
            'position_id.exists'            => 'The selected position is not valid.',
            'whatsapp_mobile.regex'         => 'Enter a valid mobile number (digits, +, -, spaces, brackets only).',
            'nic_number.regex'              => 'Enter a valid NIC — either 9 digits followed by V/X (old format) or 12 digits (new format).',
            'nic_number.unique'             => 'This NIC number is already registered to another employee.',
            'date_of_birth.before'          => 'Date of birth must be in the past.',
            'date_of_birth.after'           => 'Date of birth seems too far in the past — please verify.',
            'date_of_appointment.before_or_equal' => 'Date of appointment cannot be in the future.',
            'date_reported_for_duty.before_or_equal' => 'Date reported for duty cannot be in the future.',
            'date_confirmed.required_if' => 'Enter the confirmation date, or uncheck "Confirmed in Service" if this employee is still on probation.',
            'retirement_age.min'            => 'Retirement age must be at least 50.',
            'retirement_age.max'            => 'Retirement age cannot exceed 70.',
        ];
    }

    /**
     * Prepare incoming data before validation.
     * Normalise is_active checkbox (absent checkbox = 0).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'    => $this->boolean('is_active'),
            'is_confirmed' => $this->boolean('is_confirmed'),
            'nic_number'   => $this->filled('nic_number') ? strtoupper(trim((string) $this->input('nic_number'))) : null,
        ]);
    }
}
