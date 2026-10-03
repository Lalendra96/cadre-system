@extends('layouts.app')

@section('title', 'Intern Batch Governance')

@section('content')
    <div
        style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:16px;">
        <div>
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:4px;">
                Intern Medical Officer Allocation / Batch Governance
            </div>
            <h2 class="md-headline-sm">{{ $batch->name }}</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                Formal ownership, official batch dates and responsibility history.
            </p>
        </div>

        <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--outlined">
            ← Back to Batches
        </a>
    </div>

    @if (session('success'))
        <div
            style="background:var(--md-success-container,#d8f5e5);color:var(--md-on-success-container,#0b5c36);padding:12px 16px;border-radius:10px;margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div
            style="background:var(--md-error-container,#fde8e7);color:var(--md-on-error-container,#8c1d18);padding:12px 16px;border-radius:10px;margin-bottom:16px;">
            <strong>Please correct the following:</strong>
            <ul style="margin:8px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="md-card md-card--elevated" style="margin-bottom:16px;">
        <div class="md-card__body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;">
                <div>
                    <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Responsible Subject Officer</div>
                    <div class="md-title-md" style="margin-top:4px;">
                        {{ $batch->assignedSubjectOfficer?->name ?? 'Not Assigned' }}
                    </div>
                </div>
                <div>
                    <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Batch Start</div>
                    <div class="md-title-md" style="margin-top:4px;">
                        {{ $batch->start_date?->format('d M Y') ?? 'Not Set' }}
                    </div>
                </div>
                <div>
                    <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Batch End</div>
                    <div class="md-title-md" style="margin-top:4px;">
                        {{ $batch->end_date?->format('d M Y') ?? 'Not Set' }}
                    </div>
                </div>
                <div>
                    <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Operational Status</div>
                    <div style="margin-top:6px;">
                        <span
                            class="md-badge {{ $batch->operational_status === 'ongoing' ? 'md-badge--success' : 'md-badge--neutral' }}">
                            {{ ucfirst($batch->operational_status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canManageResponsibility)
        <div class="md-card md-card--elevated" style="margin-bottom:16px;">
            <div class="md-card__body">
                <h3 class="md-title-lg">
                    {{ $batch->assigned_subject_officer_id ? 'Reassign Responsible Subject Officer' : 'Assign Responsible Subject Officer' }}
                </h3>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:6px;max-width:900px;">
                    Only the officer assigned here receives operational write access to this batch. All other Subject
                    Officers,
                    Admin and Planning users remain read-only. This action is retained in the responsibility history and
                    audit trail.
                </p>
            </div>

            <form method="POST" action="{{ route('intern-batches.responsibility.update', $batch) }}" class="md-card__body"
                style="border-top:1px solid var(--md-outline-variant);">
                @csrf
                @method('PATCH')

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;">
                    <label class="md-field">
                        <span class="md-field__label">Responsible Subject Officer *</span>
                        <select name="assigned_subject_officer_id" class="md-field__input" required>
                            <option value="">— Select authorised Subject Officer —</option>
                            @foreach ($subjectOfficers as $officer)
                                <option value="{{ $officer->id }}" @selected((int) old('assigned_subject_officer_id', $batch->assigned_subject_officer_id) === (int) $officer->id)>
                                    {{ $officer->name }}{{ $officer->email ? ' — ' . $officer->email : '' }}
                                </option>
                            @endforeach
                        </select>

                        @if ($subjectOfficers->isEmpty())
                            <span class="md-body-sm" style="margin-top:6px;color:var(--md-error);">
                                No active Subject Officer accounts are available. Check Users → Roles and ensure the officer
                                has the Subject Officer role and is active.
                            </span>
                        @endif
                    </label>

                    <label class="md-field">
                        <span class="md-field__label">Effective Date *</span>
                        <input type="date" name="effective_date" class="md-field__input"
                            value="{{ old('effective_date', now()->format('Y-m-d')) }}" required>
                    </label>

                    <label class="md-field">
                        <span class="md-field__label">Reference No.</span>
                        <input type="text" name="reference_no" class="md-field__input" maxlength="100"
                            value="{{ old('reference_no') }}" placeholder="Optional order / minute / file reference">
                    </label>
                </div>

                <div
                    style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-top:14px;">
                    <label class="md-field">
                        <span class="md-field__label">Initial / Confirmed Batch Start Date</span>
                        <input type="date" name="start_date" class="md-field__input"
                            value="{{ old('start_date', $batch->start_date?->format('Y-m-d')) }}">
                    </label>

                    <label class="md-field">
                        <span class="md-field__label">Initial / Confirmed Batch End Date</span>
                        <input type="date" name="end_date" class="md-field__input"
                            value="{{ old('end_date', $batch->end_date?->format('Y-m-d')) }}">
                    </label>
                </div>

                <label class="md-field" style="display:block;margin-top:14px;">
                    <span class="md-field__label">Reason / Authority *</span>
                    <textarea name="reason" class="md-field__input" rows="3" minlength="5" maxlength="500" required
                        placeholder="State why responsibility is being assigned or changed and the administrative authority for the change.">{{ old('reason') }}</textarea>
                </label>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
                    <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--text">Cancel</a>
                    <button type="submit" class="md-btn md-btn--filled">
                        {{ $batch->assigned_subject_officer_id ? 'Confirm Reassignment' : 'Assign Responsibility' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if (\App\Services\InternAllocationAccessService::ownsBatch(auth()->user(), $batch))
        <div class="md-card md-card--elevated" style="margin-bottom:16px;">
            <div class="md-card__body">
                <h3 class="md-title-lg">Official Batch Period</h3>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:6px;">
                    Date corrections after responsibility has been assigned are restricted to the responsible Subject
                    Officer and require a reason.
                </p>
            </div>

            <form method="POST" action="{{ route('intern-batches.period.update', $batch) }}" class="md-card__body"
                style="border-top:1px solid var(--md-outline-variant);">
                @csrf
                @method('PATCH')

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
                    <label class="md-field">
                        <span class="md-field__label">Start Date *</span>
                        <input type="date" name="start_date" class="md-field__input"
                            value="{{ old('start_date', $batch->start_date?->format('Y-m-d')) }}" required>
                    </label>

                    <label class="md-field">
                        <span class="md-field__label">End Date *</span>
                        <input type="date" name="end_date" class="md-field__input"
                            value="{{ old('end_date', $batch->end_date?->format('Y-m-d')) }}" required>
                    </label>
                </div>

                <label class="md-field" style="display:block;margin-top:14px;">
                    <span class="md-field__label">Correction Reason *</span>
                    <input type="text" name="reason" class="md-field__input" minlength="5" maxlength="500"
                        required placeholder="Why are the official dates being changed?">
                </label>

                <div style="display:flex;justify-content:flex-end;margin-top:14px;">
                    <button type="submit" class="md-btn md-btn--filled">Save Official Dates</button>
                </div>
            </form>
        </div>
    @endif

    <div class="md-card md-card--elevated" style="margin-bottom:16px;">
        <div class="md-card__body">
            <h3 class="md-title-lg">Responsibility History</h3>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:6px;">
                Historical assignments are retained and cannot be silently overwritten.
            </p>
        </div>

        <div style="overflow-x:auto;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Effective Date</th>
                        <th>Previous Officer</th>
                        <th>New Officer</th>
                        <th>Changed By</th>
                        <th>Reference</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($responsibilityHistory as $history)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($history->effective_date)->format('d M Y') }}</td>
                            <td>{{ $history->previous_user_name ?? 'Unassigned' }}</td>
                            <td>{{ $history->new_user_name }}</td>
                            <td>{{ $history->changed_by_name }}</td>
                            <td>{{ $history->reference_no ?: '—' }}</td>
                            <td>{{ $history->reason }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="md-table__empty">No responsibility changes have been recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="md-card" style="padding:16px;">
        <strong>Governance safeguards</strong>
        <ul class="md-body-sm" style="margin:8px 0 0 18px;color:var(--md-on-surface-variant);line-height:1.7;">
            <li>One Subject Officer is the accountable operational owner of a batch at a time.</li>
            <li>Other Subject Officers, Admin and Planning users are read-only for operational data.</li>
            <li>Responsibility changes require a reason, effective date and authenticated Super Admin action.</li>
            <li>Batch date changes are audit logged and cannot be silently substituted.</li>
            <li>RHO placement records remain linked to the original ending-intern list and are not recreated manually.</li>
        </ul>
    </div>
@endsection
