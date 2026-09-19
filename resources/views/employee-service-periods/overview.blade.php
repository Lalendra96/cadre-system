@extends('layouts.app')

@section('title', 'Service History')

@section('content')
<div class="page-header">
    <div>
        <div class="page-eyebrow">Service Records</div>

        <h1 class="page-title">
            📂 Employee Service History
        </h1>

        <p class="page-subtitle">
            Career service and institutional postings are kept separately so
            transfers do not restart an officer's public/combined service.
        </p>
    </div>

    @if (auth()->user()->isSubjectOfficer() || auth()->user()->isSuperAdmin())
        <a
            class="md-btn md-btn--filled"
            href="{{ route('incoming-officers.create') }}"
        >
            ➕ Register Incoming Officer
        </a>
    @endif
</div>

@if ($incomplete->count())
    <div
        class="workforce-panel"
        style="
            border-left: 4px solid var(--md-warning);
            margin-bottom: 16px;
        "
    >
        <strong>
            📂 {{ $incomplete->count() }} profiles need service-history completion
        </strong>

        <div class="md-body-sm">
            These records are missing a public-service start date or have no
            structured service periods.
        </div>
    </div>
@endif

<div class="workforce-panel">
    <table class="md-table">
        <thead>
            <tr>
                <th>Officer</th>
                <th>Current Position</th>
                <th>Public Service</th>
                <th>Recorded Periods</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td>
                        <strong>{{ $employee->display_name }}</strong>

                        <div class="md-body-sm">
                            {{ $employee->pay_no ?: 'No Pay No.' }}
                        </div>
                    </td>

                    <td>
                        {{ $employee->position?->title ?? '—' }}
                    </td>

                    <td>
                        {{
                            $employee->date_joined_public_service?->format('d M Y')
                                ?? 'Missing'
                        }}
                    </td>

                    <td>
                        {{ $employee->servicePeriods->count() }}
                    </td>

                    <td>
                        <a
                            href="{{ route('employee-service-periods.index', $employee) }}"
                        >
                            Open history →
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="md-table__empty">
                        No allocated employees.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
