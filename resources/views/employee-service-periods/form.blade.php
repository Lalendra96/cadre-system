@extends('layouts.app')

@section(
    'title',
    $period->exists
        ? 'Edit Service Period'
        : 'Add Service Period'
)

@section('content')
<div class="page-header">
    <div>
        <a href="{{ route('employee-service-periods.index', $employee) }}">
            ← Service History
        </a>

        <h1 class="page-title">
            {{ $period->exists ? 'Edit' : 'Add' }} Service Period
        </h1>

        <p class="page-subtitle">
            Use the wording from the service record/transfer document.
            Historic institutions and positions may be typed even if they
            are not in current hospital master data.
        </p>
    </div>
</div>

@if ($errors->any())
    <div
        class="workforce-panel"
        style="
            border-left: 4px solid var(--md-error);
            margin-bottom: 16px;
        "
    >
        <strong>We couldn't save this record yet.</strong>

        <div class="md-body-sm">
            Please complete the highlighted items. Your entered information
            has been kept.
        </div>
    </div>
@endif

<form
    method="POST"
    action="{{
        $period->exists
            ? route('employee-service-periods.update', [$employee, $period])
            : route('employee-service-periods.store', $employee)
    }}"
    class="workforce-panel"
>
    @csrf

    @if ($period->exists)
        @method('PUT')
    @endif

    <div
        style="
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        "
    >
        <div class="md-field">
            <label class="md-field__label">
                What are you recording?
            </label>

            <select
                class="md-field__input"
                name="period_kind"
                required
            >
                @foreach (
                    [
                        'posting' => 'Institution / Posting',
                        'career' => 'Career / Service period',
                        'attachment' => 'Temporary attachment',
                        'secondment' => 'Secondment',
                        'other' => 'Other',
                    ]
                    as $value => $label
                )
                    <option
                        value="{{ $value }}"
                        @selected(
                            old(
                                'period_kind',
                                $period->period_kind ?: 'posting'
                            ) === $value
                        )
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Government / service sector
            </label>

            <select
                class="md-field__input"
                name="sector"
            >
                <option value="">— Select —</option>

                @foreach (
                    [
                        'central_government' => 'Central Government',
                        'provincial_public_service' => 'Provincial Public Service',
                        'state_corporation' => 'State corporation / board',
                        'other' => 'Other',
                    ]
                    as $value => $label
                )
                    <option
                        value="{{ $value }}"
                        @selected(
                            old('sector', $period->sector) === $value
                        )
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Service (e.g. Development Officers' Service)
            </label>

            <input
                class="md-field__input"
                name="service_name"
                value="{{ old('service_name', $period->service_name) }}"
            >
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Ministry / Department
            </label>

            <input
                class="md-field__input"
                name="ministry_department"
                value="{{ old('ministry_department', $period->ministry_department) }}"
            >
        </div>

        <div
            class="md-field"
            style="grid-column: 1 / -1;"
        >
            <label class="md-field__label">
                Institution / workplace *
            </label>

            <input
                class="md-field__input"
                name="institution_name"
                required
                value="{{ old('institution_name', $period->institution_name) }}"
                placeholder="e.g. District Secretariat, Matale"
            >
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Current-master position (optional)
            </label>

            <select
                class="md-field__input"
                name="position_id"
            >
                <option value="">
                    — Historic/external position —
                </option>

                @foreach ($positions as $position)
                    <option
                        value="{{ $position->id }}"
                        @selected(
                            old(
                                'position_id',
                                $period->position_id
                            ) == $position->id
                        )
                    >
                        {{ $position->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Historic / external position text
            </label>

            <input
                class="md-field__input"
                name="position_text"
                value="{{ old('position_text', $period->position_text) }}"
                placeholder="Type exactly as shown in the service record"
            >
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Configured grade (optional)
            </label>

            <select
                class="md-field__input"
                name="position_grade_id"
            >
                <option value="">
                    — External / historic grade —
                </option>

                @foreach ($grades as $grade)
                    <option
                        value="{{ $grade->id }}"
                        @selected(
                            old(
                                'position_grade_id',
                                $period->position_grade_id
                            ) == $grade->id
                        )
                    >
                        {{ $grade->position?->title }} — {{ $grade->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Historic grade text
            </label>

            <input
                class="md-field__input"
                name="grade_text"
                value="{{ old('grade_text', $period->grade_text) }}"
            >
        </div>

        <div class="md-field">
            <label class="md-field__label">
                From date *
            </label>

            <input
                type="date"
                class="md-field__input"
                name="start_date"
                required
                value="{{
                    old(
                        'start_date',
                        $period->start_date?->format('Y-m-d')
                    )
                }}"
            >
        </div>

        <div class="md-field">
            <label class="md-field__label">
                To date
            </label>

            <input
                type="date"
                class="md-field__input"
                name="end_date"
                value="{{
                    old(
                        'end_date',
                        $period->end_date?->format('Y-m-d')
                    )
                }}"
            >

            <div class="md-body-sm">
                Leave blank for the current posting.
            </div>
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Movement / reason
            </label>

            <select
                class="md-field__input"
                name="movement_type"
            >
                <option value="">— Select —</option>

                @foreach (
                    \App\Models\EmployeeServicePeriod::MOVEMENT_TYPES
                    as $value => $label
                )
                    <option
                        value="{{ $value }}"
                        @selected(
                            old(
                                'movement_type',
                                $period->movement_type
                            ) === $value
                        )
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Reference / order number
            </label>

            <input
                class="md-field__input"
                name="movement_reference"
                value="{{
                    old(
                        'movement_reference',
                        $period->movement_reference
                    )
                }}"
            >
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Reference date
            </label>

            <input
                type="date"
                class="md-field__input"
                name="movement_reference_date"
                value="{{
                    old(
                        'movement_reference_date',
                        $period->movement_reference_date?->format('Y-m-d')
                    )
                }}"
            >
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Evidence document
            </label>

            <select
                class="md-field__input"
                name="source_document_id"
            >
                <option value="">
                    — Document not yet linked —
                </option>

                @foreach ($documents as $document)
                    <option
                        value="{{ $document->id }}"
                        @selected(
                            old(
                                'source_document_id',
                                $period->source_document_id
                            ) == $document->id
                        )
                    >
                        {{ $document->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Verification status *
            </label>

            <select
                class="md-field__input"
                name="verification_status"
                required
            >
                @foreach (
                    \App\Models\EmployeeServicePeriod::VERIFICATION
                    as $value => $label
                )
                    <option
                        value="{{ $value }}"
                        @selected(
                            old(
                                'verification_status',
                                $period->verification_status
                                    ?: 'document_pending'
                            ) === $value
                        )
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div
            style="
                grid-column: 1 / -1;
                display: flex;
                gap: 18px;
                flex-wrap: wrap;
            "
        >
            <label>
                <input
                    type="checkbox"
                    name="counts_for_service"
                    value="1"
                    @checked(
                        old(
                            'counts_for_service',
                            $period->exists
                                ? $period->counts_for_service
                                : true
                        )
                    )
                >
                Counts toward service
            </label>

            <label>
                <input
                    type="checkbox"
                    name="counts_for_grade_service"
                    value="1"
                    @checked(
                        old(
                            'counts_for_grade_service',
                            $period->exists
                                ? $period->counts_for_grade_service
                                : true
                        )
                    )
                >
                Counts toward grade service
            </label>

            <label>
                <input
                    type="checkbox"
                    name="counts_for_pension"
                    value="1"
                    @checked(
                        old(
                            'counts_for_pension',
                            $period->exists
                                ? $period->counts_for_pension
                                : true
                        )
                    )
                >
                Counts toward pension
            </label>

            <label>
                <input
                    type="checkbox"
                    name="is_current"
                    value="1"
                    @checked(
                        old(
                            'is_current',
                            $period->is_current
                        )
                    )
                >
                Current posting
            </label>
        </div>

        <div
            class="md-field"
            style="grid-column: 1 / -1;"
        >
            <label class="md-field__label">
                Remarks
            </label>

            <textarea
                class="md-field__input"
                name="remarks"
                rows="3"
            >{{ old('remarks', $period->remarks) }}</textarea>
        </div>
    </div>

    <div
        style="
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 18px;
        "
    >
        <a
            class="md-btn md-btn--text"
            href="{{ route('employee-service-periods.index', $employee) }}"
        >
            Cancel
        </a>

        <button class="md-btn md-btn--filled">
            Review & Save Service Period
        </button>
    </div>
</form>
@endsection
