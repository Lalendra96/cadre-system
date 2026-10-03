@extends('layouts.app')
@section('title', 'Assignments — ' . $batch->name)
@section('content')

    <div
        style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--icon">&#8592;</a>
            <div>
                <h2 class="md-headline-sm">{{ $batch->name }} — Assignments</h2>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Responsible officer: {{ $batch->assignedSubjectOfficer?->name ?? 'Unassigned' }}
                </p>
            </div>
        </div>

        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @unless ($canEdit)
                <span class="md-badge md-badge--neutral">Read-only oversight</span>
            @endunless
            <a href="{{ route('intern-batches.export.pdf', $batch) }}" class="md-btn md-btn--outlined">📄 Export PDF</a>
            <a href="{{ route('intern-batches.export.csv', $batch) }}" class="md-btn md-btn--outlined">📊 Export CSV</a>
        </div>
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

    @if ($canEdit && $unassignedInterns->isNotEmpty())
        <div class="md-card md-card--elevated" style="padding:16px;margin-bottom:20px;">
            <h3 class="md-label-md" style="margin-bottom:10px;">Manually Assign</h3>
            <form method="POST" action="{{ route('intern-batches.assignments.assign', $batch) }}"
                style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                @csrf
                <div>
                    <label class="md-body-sm" style="display:block;color:var(--md-on-surface-variant);">Intern</label>
                    <select name="intern_id" class="md-field__input" style="min-width:200px;" required>
                        <option value="">Select…</option>
                        @foreach ($unassignedInterns as $intern)
                            <option value="{{ $intern->id }}">{{ $intern->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="md-body-sm" style="display:block;color:var(--md-on-surface-variant);">Appointment</label>
                    <select name="appointment_number" class="md-field__input" required>
                        <option value="1">1st</option>
                        <option value="2">2nd</option>
                    </select>
                </div>
                <div>
                    <label class="md-body-sm" style="display:block;color:var(--md-on-surface-variant);">Rotation
                        Unit</label>
                    <select name="intern_rotation_unit_id" class="md-field__input" style="min-width:200px;" required>
                        @foreach ($rotationUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="md-btn md-btn--filled">Assign</button>
            </form>
        </div>
    @elseif(!$canEdit)
        <div class="md-card"
            style="padding:14px;margin-bottom:16px;background:var(--md-secondary-container);color:var(--md-on-secondary-container);">
            Allocation is read-only. Only the assigned Subject Officer may add or clear assignment slots.
        </div>
    @endif

    <div style="overflow-x:auto;">
        <table class="md-table" style="min-width:700px;">
            <thead>
                <tr>
                    <th>Rotation Unit</th>
                    <th>1st Appointment</th>
                    <th>2nd Appointment</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rotationUnits as $unit)
                    <tr>
                        <td class="md-label-md" style="vertical-align:top;padding-top:14px;">{{ $unit->name }}</td>
                        @foreach ([1, 2] as $appointmentNumber)
                            <td style="vertical-align:top;">
                                @forelse($grid[$unit->id][$appointmentNumber] as $slotNumber => $assignment)
                                    <div
                                        style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;border-bottom:1px solid var(--md-outline-variant);font-size:12.5px;">
                                        <span>{{ $slotNumber }}. {{ $assignment?->intern->name ?? '—' }}</span>
                                        @if ($assignment && $canEdit)
                                            <form method="POST"
                                                action="{{ route('intern-batches.assignments.unassign', [$batch, $assignment]) }}"
                                                onsubmit="return confirm('Clear this assignment slot? The action will be audit logged.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="md-btn md-btn--text"
                                                    style="font-size:10px;color:var(--md-error);padding:0;">✕</button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <span class="md-body-sm" style="color:var(--md-on-surface-variant);">No capacity
                                        set</span>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('intern-batches.rho-placements.index', $batch) }}" class="md-btn md-btn--outlined">Next: Ending
            Intern List / RHO Placements →</a>
    </div>
@endsection
