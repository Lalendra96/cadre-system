@extends('layouts.app')
@section('title', 'Interns — ' . $batch->name)
@section('content')

    <div
        style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--icon">&#8592;</a>
            <div>
                <h2 class="md-headline-sm">{{ $batch->name }} — Intern List</h2>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    {{ $interns->count() }} active intern(s) · Responsible officer:
                    {{ $batch->assignedSubjectOfficer?->name ?? 'Unassigned' }}
                </p>
            </div>
        </div>

        @unless ($canEdit)
            <span class="md-badge md-badge--neutral">Read-only oversight</span>
        @endunless
    </div>

    @if (session('success'))
        <div
            style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div
            style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
            {{ session('error') }}
        </div>
    @endif

    @if ($canEdit)
        <form method="POST" action="{{ route('intern-batches.interns.upload', $batch) }}" enctype="multipart/form-data"
            class="md-card md-card--elevated"
            style="padding:16px;margin-bottom:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv,.docx" required>
            <button type="submit" class="md-btn md-btn--filled">Upload List</button>
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
                Source file is used only for import and then removed from temporary storage. Existing records are not
                hard-deleted.
            </span>
        </form>
    @else
        <div class="md-card"
            style="padding:14px;margin-bottom:16px;background:var(--md-secondary-container);color:var(--md-on-secondary-container);">
            This list is read-only for your account. Only the assigned Subject Officer may import or amend intern records.
        </div>
    @endif

    <div class="md-card md-card--elevated">
        <div style="overflow-x:auto;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>NIC Number</th>
                        <th>1st Appointment</th>
                        <th>2nd Appointment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($interns as $intern)
                        @php
                            $firstAppointment = $intern->assignments->firstWhere('appointment_number', 1);
                            $secondAppointment = $intern->assignments->firstWhere('appointment_number', 2);
                        @endphp
                        <tr>
                            <td class="md-label-md">{{ $intern->name }}</td>
                            <td>
                                @if ($canEdit)
                                    <form method="POST"
                                        action="{{ route('intern-batches.interns.nic', [$batch, $intern]) }}"
                                        style="display:flex;gap:6px;align-items:center;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="nic_number" value="{{ $intern->nic_number }}"
                                            maxlength="12" placeholder="912345678V" class="md-field__input"
                                            style="width:130px;font-size:12px;">
                                        <button type="submit" class="md-btn md-btn--text"
                                            style="font-size:11px;">Save</button>
                                    </form>
                                @else
                                    {{ $intern->nic_number ?: '—' }}
                                @endif
                            </td>
                            <td>{{ $firstAppointment ? $firstAppointment->rotationUnit->name . ' (#' . $firstAppointment->slot_number . ')' : '—' }}
                            </td>
                            <td>{{ $secondAppointment ? $secondAppointment->rotationUnit->name . ' (#' . $secondAppointment->slot_number . ')' : '—' }}
                            </td>
                            <td>
                                @if ($canEdit)
                                    <details>
                                        <summary class="md-body-sm" style="cursor:pointer;color:var(--md-error);">Disable
                                            record</summary>
                                        <form method="POST"
                                            action="{{ route('intern-batches.interns.disable', [$batch, $intern]) }}"
                                            style="margin-top:6px;min-width:220px;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="disable_reason" class="md-field__input"
                                                placeholder="Reason required" minlength="5" maxlength="500" required>
                                            <button type="submit" class="md-btn md-btn--outlined"
                                                style="margin-top:6px;">Confirm Disable</button>
                                        </form>
                                    </details>
                                @else
                                    <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Read only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="md-table__empty">No interns uploaded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('intern-batches.assignments.show', $batch) }}" class="md-btn md-btn--outlined">Next: Assignments
            →</a>
        <a href="{{ route('intern-batches.rho-placements.index', $batch) }}" class="md-btn md-btn--text">Ending Intern List
            / RHO Placements</a>
    </div>
@endsection
