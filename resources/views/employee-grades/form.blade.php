@extends('layouts.app')
@section('title', 'Add Grade Record — ' . $employee->display_name)
@section('content')

<div style="max-width:560px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('employee-grades.index', $employee) }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Propose Grade Record</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $employee->display_name }} ({{ $employee->pay_no ?? '—' }}) — {{ $employee->position->title ?? '—' }}
            </p>
        </div>
    </div>

    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($availableGrades->isEmpty())
        <div class="md-card md-card--elevated" style="padding:32px;text-align:center;color:var(--md-on-surface-variant);">
            <div style="font-size:28px;margin-bottom:8px;">📋</div>
            @if(! $employee->position)
                <p class="md-body-sm" style="margin-bottom:12px;">
                    This employee has no position assigned — assign a position on their profile before grades can be configured or recorded.
                </p>
            @else
                <p class="md-body-sm" style="margin-bottom:12px;">
                    No grades are configured yet for <strong>{{ $employee->position->title }}</strong>.
                </p>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isPlanningOfficer() || auth()->user()->isSubjectOfficer())
                    <a href="{{ route('position-grades.create', $employee->position) }}" class="md-btn md-btn--filled">
                        + Configure Grades for {{ $employee->position->title }}
                    </a>
                @else
                    <p class="md-body-sm">Please ask your Planning Officer to configure grading criteria for this position first.</p>
                @endif
            @endif
        </div>
    @else
    <form method="POST" action="{{ route('employee-grades.store', $employee) }}" class="md-card md-card--elevated">
        @csrf

        <div class="md-card__body">
            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">
                    Grade <span style="color:var(--md-error)">*</span>
                </label>
                <select name="position_grade_id" class="md-field__input @error('position_grade_id') md-field--error @enderror" required>
                    <option value="">— Select grade —</option>
                    @foreach($availableGrades as $g)
                        <option value="{{ $g->id }}" {{ old('position_grade_id') == $g->id ? 'selected' : '' }}>
                            {{ $g->name }}
                        </option>
                    @endforeach
                </select>
                @error('position_grade_id')<div class="md-field__error">{{ $message }}</div>@enderror

                <div id="criteriaHint" class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:6px;"></div>
            </div>

            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">
                    Effective Date <span style="color:var(--md-error)">*</span>
                </label>
                <input type="date" name="effective_date"
                       class="md-field__input @error('effective_date') md-field--error @enderror"
                       value="{{ old('effective_date', now()->toDateString()) }}" required>
                @error('effective_date')<div class="md-field__error">{{ $message }}</div>@enderror
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    If approved, the previous current grade record will be closed automatically. Nothing is applied until an independent authorised officer approves this proposal.
                </div>
            </div>

            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Reference No.</label>
                <input type="text" name="reference_no"
                       class="md-field__input @error('reference_no') md-field--error @enderror"
                       value="{{ old('reference_no') }}" placeholder="e.g. promotion letter reference" maxlength="60">
                @error('reference_no')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">Notes</label>
                <textarea name="notes" rows="2" maxlength="500"
                          class="md-field__input @error('notes') md-field--error @enderror"
                          placeholder="Optional">{{ old('notes') }}</textarea>
                @error('notes')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('employee-grades.index', $employee) }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">✅ Submit for Independent Approval</button>
        </div>
    </form>
    @endif
</div>

@endsection

@push('scripts')
<script>
// Shows each grade's configured criteria summary as a hint when selected —
// pulled from a small inline map rendered server-side, no extra request needed.
(function () {
    var criteriaMap = {
        @foreach($availableGrades as $g)
            {{ $g->id }}: {!! \Illuminate\Support\Js::from($g->criteriaSummary()) !!},
        @endforeach
    };
    var select = document.querySelector('select[name="position_grade_id"]');
    var hint   = document.getElementById('criteriaHint');
    if (!select || !hint) return;

    function updateHint() {
        var text = criteriaMap[select.value];
        hint.textContent = text ? 'Criteria: ' + text : '';
    }
    select.addEventListener('change', updateHint);
    updateHint();
})();
</script>
@endpush
