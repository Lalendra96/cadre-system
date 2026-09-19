<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $employee = $this->route('employee');
        $employeeId = $employee?->id;
        $user = $this->user();

        $subjectCodeRule = $user->isSuperAdmin()
            || $user->isPlanningOfficer()
                ? [
                    'integer',
                    'exists:subject_codes,id',
                ]
                : [
                    'integer',
                    Rule::in(
                        $user
                            ->effectiveHrSubjectCodeIds()
                            ->all()
                    ),
                ];

        $positionRule = $user->isSuperAdmin()
            || $user->isPlanningOfficer()
                ? [
                    'integer',
                    'exists:positions,id',
                ]
                : [
                    'integer',
                    Rule::in(
                        $user
                            ->effectiveHrPositionIds()
                            ->all()
                    ),
                ];

        return [
            'salutation' => [
                'nullable',
                'string',
                Rule::in([
                    'Mr.',
                    'Mrs.',
                    'Ms.',
                    'Miss',
                    'Dr.',
                    'Rev.',
                    'Prof.',
                    'Eng.',
                ]),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'pay_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(
                    'employees',
                    'pay_no'
                )->ignore($employeeId),
            ],
            'service_file_no' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique(
                    'employees',
                    'service_file_no'
                )->ignore($employeeId),
            ],
            'nic_number' => [
                'nullable',
                'string',
                'max:12',
                'regex:/^(\d{9}[VvXx]|\d{12})$/',
                Rule::unique(
                    'employees',
                    'nic_number'
                )->ignore($employeeId),
            ],
            'wop_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique(
                    'employees',
                    'wop_number'
                )->ignore($employeeId),
            ],
            'professional_registration_no' => [
                'nullable',
                'string',
                'max:80',
            ],
            'professional_registration_expiry' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'gender' => [
                'required',
                Rule::in([
                    'M',
                    'F',
                    'O',
                ]),
            ],
            'preferred_language' => [
                'required',
                Rule::in([
                    'en',
                    'si',
                    'ta',
                ]),
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
            'whatsapp_mobile' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s\(\)]+$/',
            ],
            'permanent_address' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'current_address' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'emergency_contact_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'emergency_contact_relationship' => [
                'nullable',
                'string',
                'max:80',
            ],
            'emergency_contact_mobile' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9+\-\s\(\)]+$/',
            ],
            'subject_code_id' => array_merge(
                ['required'],
                $subjectCodeRule
            ),
            'position_id' => array_merge(
                ['required'],
                $positionRule
            ),
            'unit_id' => [
                'required',
                'integer',
                'exists:units,id',
            ],
            'salary_scale_id' => [
                'nullable',
                'integer',
                'exists:salary_scales,id',
            ],
            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
                'after:1940-01-01',
            ],
            'date_of_appointment' => [
                'nullable',
                'date',
                'before_or_equal:today',
                'after:1980-01-01',
            ],
            'date_joined_public_service' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'date_joined_combined_service' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'date_joined_institution' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'date_current_grade' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'last_increment_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'next_increment_date' => [
                'nullable',
                'date',
            ],
            'date_reported_for_duty' => [
                'nullable',
                'date',
                'before_or_equal:today',
                'after:1980-01-01',
            ],
            'retirement_age' => [
                'nullable',
                'integer',
                'min:50',
                'max:70',
            ],
            'is_confirmed' => [
                'boolean',
            ],
            'date_confirmed' => [
                'nullable',
                'date',
                'before_or_equal:today',
                'required_if:is_confirmed,1',
            ],
            'confirmation_reference_no' => [
                'nullable',
                'string',
                'max:60',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'boolean',
            ],
            'current_service_name' => [
                'nullable',
                'string',
                'max:180',
            ],
            'combined_service_name' => [
                'nullable',
                'string',
                'max:180',
            ],
            'employment_status' => [
                'required',
                Rule::in([
                    'active',
                    'on_leave',
                    'no_pay_leave',
                    'temporary_transfer',
                    'permanent_transfer',
                    'secondment',
                    'deputation',
                    'acting',
                    'interdicted',
                    'suspended',
                    'resigned',
                    'retired',
                    'deceased',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_code_id.in'
                => 'The selected Subject Code is not linked to a Position assigned to you for HR management.',
            'position_id.in'
                => 'You may only manage employees in Positions assigned to you for HR responsibility.',
            'position_id.required'
                => 'Please select the position this employee holds.',
            'position_id.exists'
                => 'The selected position is not valid.',
            'whatsapp_mobile.regex'
                => 'Enter a valid mobile number (digits, +, -, spaces, brackets only).',
            'nic_number.regex'
                => 'Enter a valid NIC — either 9 digits followed by V/X (old format) or 12 digits (new format).',
            'nic_number.unique'
                => 'This NIC number is already registered to another employee.',
            'date_of_birth.before'
                => 'Date of birth must be in the past.',
            'date_of_birth.after'
                => 'Date of birth seems too far in the past — please verify.',
            'date_of_appointment.before_or_equal'
                => 'Date of appointment cannot be in the future.',
            'date_reported_for_duty.before_or_equal'
                => 'Date reported for duty cannot be in the future.',
            'date_confirmed.required_if'
                => 'Enter the confirmation date, or uncheck "Confirmed in Service" if this employee is still on probation.',
            'retirement_age.min'
                => 'Retirement age must be at least 50.',
            'retirement_age.max'
                => 'Retirement age cannot exceed 70.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'employment_status' => $this->input(
                'employment_status',
                'active'
            ),
            'is_confirmed' => $this->boolean(
                'is_confirmed'
            ),
            'nic_number' => $this->filled('nic_number')
                ? strtoupper(
                    trim(
                        (string) $this->input('nic_number')
                    )
                )
                : null,
        ]);
    }
}
