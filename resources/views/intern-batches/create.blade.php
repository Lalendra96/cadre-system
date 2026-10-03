@extends('layouts.app')
@section('title', 'New Intern Batch')
@section('content')

    <div style="max-width:760px;margin:0 auto;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--icon">&#8592;</a>
            <div>
                <h2 class="md-headline-sm">New Intern Medical Officer Batch</h2>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Record the official batch period and assign one responsible Subject Officer.
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div
                style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="md-card md-card--elevated" style="margin-bottom:16px;padding:16px;">
            <strong>Governed responsibility</strong>
            <p class="md-body-sm" style="margin:6px 0 0;color:var(--md-on-surface-variant);">
                Only the Subject Officer assigned to this batch can change capacities, intern records, allocations and RHO
                placements.
                Admin, Planning and other authorised officers retain read-only oversight. Responsibility changes are audit
                logged.
            </p>
        </div>

        <form method="POST" action="{{ route('intern-batches.store') }}" class="md-card md-card--elevated">
            @csrf

            <div class="md-card__body">
                <div class="md-field" style="margin-bottom:18px;">
                    <label class="md-field__label">Batch Name</label>
                    <input type="text" name="name" class="md-field__input" value="{{ old('name') }}"
                        placeholder="e.g. 2026/02 or August 2026" maxlength="100" required autofocus>
                </div>

                <div
                    style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:18px;">
                    <div class="md-field">
                        <label class="md-field__label">Batch Start Date</label>
                        <input type="date" name="start_date" class="md-field__input" value="{{ old('start_date') }}"
                            required>
                    </div>

                    <div class="md-field">
                        <label class="md-field__label">Batch End Date</label>
                        <input type="date" name="end_date" class="md-field__input" value="{{ old('end_date') }}"
                            required>
                    </div>
                </div>

                @if ($canAssignOfficer)
                    <div class="md-field">
                        <label class="md-field__label">Responsible Subject Officer</label>
                        <select name="assigned_subject_officer_id" class="md-field__input" required>
                            <option value="">Select responsible officer</option>
                            @foreach ($subjectOfficers as $officer)
                                <option value="{{ $officer->id }}" @selected((string) old('assigned_subject_officer_id') === (string) $officer->id)>
                                    {{ $officer->name }} — {{ $officer->email }}
                                </option>
                            @endforeach
                        </select>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:6px;">
                            Super Admin assigns formal operational ownership. The assigned officer becomes the only
                            operational editor.
                        </div>
                    </div>
                @else
                    <div class="md-card" style="padding:14px;background:var(--md-surface-container-low);">
                        <div class="md-label-md">Responsible Subject Officer</div>
                        <div class="md-body-sm" style="margin-top:4px;">{{ auth()->user()->name }} (you)</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                            This batch will be assigned to your account. Only Super Admin can formally reassign
                            responsibility later.
                        </div>
                    </div>
                @endif
            </div>

            <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
                <a href="{{ route('intern-batches.index') }}" class="md-btn md-btn--outlined">Cancel</a>
                <button type="submit" class="md-btn md-btn--filled">Create Batch</button>
            </div>
        </form>
    </div>
@endsection
