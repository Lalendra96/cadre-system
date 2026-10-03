@extends('layouts.app')
@section('title', 'RHO Placements — ' . $batch->name)
@section('content')

    <div
        style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--icon">&#8592;</a>
            <div>
                <h2 class="md-headline-sm">{{ $batch->name }} — Ending Intern List & RHO Placements</h2>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Batch end: {{ $batch->end_date?->format('d M Y') ?? 'Not recorded' }} · Responsible officer:
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

    @if ($errors->any())
        <div
            style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:16px;">
        <div class="md-card" style="padding:16px;">
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Ending intern list</div>
            <div class="md-headline-sm" style="margin-top:4px;">{{ $batch->interns->count() }}</div>
        </div>
        <div class="md-card" style="padding:16px;">
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">RHO placed</div>
            <div class="md-headline-sm" style="margin-top:4px;">{{ $placedCount }}</div>
        </div>
        <div class="md-card" style="padding:16px;">
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Pending / other</div>
            <div class="md-headline-sm" style="margin-top:4px;">{{ $batch->interns->count() - $placedCount }}</div>
        </div>
    </div>

    <div class="md-card"
        style="padding:14px;margin-bottom:16px;background:var(--md-secondary-container);color:var(--md-on-secondary-container);">
        <strong>Governed RHO capture</strong>
        <div class="md-body-sm" style="margin-top:4px;">
            This list is generated from the active interns already recorded in the ending batch; names are not re-entered.
            Record only an authoritative placement decision. The system does not recommend or infer a placement.
            A placement marked “Placed” requires institution, effective date and reference number.
        </div>
    </div>

    @if ($canEdit)
        <form method="POST" action="{{ route('intern-batches.rho-placements.update', $batch) }}"
            class="md-card md-card--elevated">
            @csrf
            @method('PUT')

            <div style="overflow-x:auto;">
                <table class="md-table" style="min-width:1200px;">
                    <thead>
                        <tr>
                            <th>Intern</th>
                            <th>Final Recorded Rotation</th>
                            <th>Status</th>
                            <th>RHO Institution</th>
                            <th>Unit / Placement</th>
                            <th>Effective Date</th>
                            <th>Reference No.</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batch->interns as $intern)
                            @php
                                $placement = $intern->rhoPlacement;
                                $lastAssignment = $intern->assignments->sortByDesc('appointment_number')->first();
                            @endphp
                            <tr>
                                <td>
                                    <div class="md-label-md">{{ $intern->name }}</div>
                                    <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
                                        {{ $intern->nic_number ?: 'NIC not recorded' }}</div>
                                </td>
                                <td>{{ $lastAssignment?->rotationUnit?->name ?? '—' }}</td>
                                <td>
                                    <select name="placements[{{ $intern->id }}][status]" class="md-field__input"
                                        required>
                                        <option value="pending" @selected(old("placements.{$intern->id}.status", $placement?->status ?? 'pending') === 'pending')>Pending</option>
                                        <option value="placed" @selected(old("placements.{$intern->id}.status", $placement?->status) === 'placed')>Placed</option>
                                        <option value="deferred" @selected(old("placements.{$intern->id}.status", $placement?->status) === 'deferred')>Deferred</option>
                                        <option value="not_placed" @selected(old("placements.{$intern->id}.status", $placement?->status) === 'not_placed')>Not placed</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="placements[{{ $intern->id }}][placement_institution]"
                                        value="{{ old("placements.{$intern->id}.placement_institution", $placement?->placement_institution) }}"
                                        class="md-field__input" maxlength="180" placeholder="Hospital / institution">
                                </td>
                                <td>
                                    <input type="text" name="placements[{{ $intern->id }}][placement_unit]"
                                        value="{{ old("placements.{$intern->id}.placement_unit", $placement?->placement_unit) }}"
                                        class="md-field__input" maxlength="180" placeholder="Unit / service">
                                </td>
                                <td>
                                    <input type="date" name="placements[{{ $intern->id }}][effective_date]"
                                        value="{{ old("placements.{$intern->id}.effective_date", $placement?->effective_date?->format('Y-m-d')) }}"
                                        class="md-field__input">
                                </td>
                                <td>
                                    <input type="text" name="placements[{{ $intern->id }}][reference_no]"
                                        value="{{ old("placements.{$intern->id}.reference_no", $placement?->reference_no) }}"
                                        class="md-field__input" maxlength="100" placeholder="Official reference">
                                </td>
                                <td>
                                    <input type="text" name="placements[{{ $intern->id }}][notes]"
                                        value="{{ old("placements.{$intern->id}.notes", $placement?->notes) }}"
                                        class="md-field__input" maxlength="1000" placeholder="Optional note">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="md-table__empty">No active interns are recorded in this batch.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
                <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--outlined">Back</a>
                <button type="submit" class="md-btn md-btn--filled">Save RHO Placements</button>
            </div>
        </form>
    @else
        <div class="md-card md-card--elevated">
            <div style="overflow-x:auto;">
                <table class="md-table">
                    <thead>
                        <tr>
                            <th>Intern</th>
                            <th>Final Recorded Rotation</th>
                            <th>Status</th>
                            <th>RHO Institution / Unit</th>
                            <th>Effective Date</th>
                            <th>Reference No.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batch->interns as $intern)
                            @php
                                $placement = $intern->rhoPlacement;
                                $lastAssignment = $intern->assignments->sortByDesc('appointment_number')->first();
                            @endphp
                            <tr>
                                <td>{{ $intern->name }}</td>
                                <td>{{ $lastAssignment?->rotationUnit?->name ?? '—' }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $placement?->status ?? 'pending')) }}</td>
                                <td>
                                    {{ $placement?->placement_institution ?? '—' }}
                                    @if ($placement?->placement_unit)
                                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">
                                            {{ $placement->placement_unit }}</div>
                                    @endif
                                </td>
                                <td>{{ $placement?->effective_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $placement?->reference_no ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="md-table__empty">No active interns are recorded in this batch.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
