        <div class="workforce-panel">
            <div class="panel-title-row">
                <div>
                    <div class="panel-title">
                        1. Employee & purpose
                    </div>

                    <div class="panel-subtitle">
                        Choose the employee first. Only records within your
                        authorised scope are shown.
                    </div>
                </div>
            </div>

            <div class="md-form-row" style="margin-top: 12px;">
                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.employee') }} *
                    </label>

                    <select class="md-select" name="employee_id" id="employee_select" required>
                        <option value="">
                            — Select employee —
                        </option>

                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}"
                                {{ (string) old('employee_id', $draft?->employee_id ?? $selectedEmployeeId) === (string) $employee->id
                                    ? 'selected'
                                    : '' }}>
                                {{ $employee->display_name }}
                                ({{ $employee->pay_no ?: 'no pay no.' }})
                                — {{ $employee->position?->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.purpose') }} *
                    </label>

                    <select class="md-select" name="purpose" id="purpose_select" required>
                        @foreach ([
        'service_confirmation' => 'Service Confirmation',
        'experience' => 'Experience / Detailed Service',
        'salary_employment' => 'Salary / Employment Verification',
        'visa_embassy' => 'Embassy / Visa',
        'foreign_employment' => 'Foreign Employment',
        'higher_studies' => 'Higher Studies / University',
        'scholarship' => 'Scholarship / Fellowship',
        'professional_registration' => 'Professional Registration',
        'bank' => 'Bank / Financial Institution',
        'transfer_release' => 'Transfer / Release',
        'retirement' => 'Retirement / Pension',
        'other' => 'Other',
    ] as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('purpose', $draft?->purpose) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md-form-group">
                    <label class="md-label">
                        {{ __('ui.language') }} *
                    </label>

                    <select class="md-select" name="language" id="language_select" required>
                        <option value="en"
                            {{ old('language', $draft?->language ?? (auth()->user()->locale ?? 'en')) === 'en' ? 'selected' : '' }}>
                            English
                        </option>

                        <option value="si" {{ old('language', $draft?->language) === 'si' ? 'selected' : '' }}>
                            සිංහල
                        </option>

                        <option value="ta" {{ old('language', $draft?->language) === 'ta' ? 'selected' : '' }}>
                            தமிழ்
                        </option>
                    </select>
                </div>
            </div>
        </div>
