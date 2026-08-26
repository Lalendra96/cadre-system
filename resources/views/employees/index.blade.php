@extends('layouts.app')
@section('title', 'Employee Profiles')
@section('content')
<div class="md-flex-between" style="margin-bottom:16px;">
    <h1 class="md-h2">Employee Profiles</h1>
    @if($canSeeInactive)
        <a href="{{ route('employees.index', ['show_inactive' => $showInactive ? 0 : 1]) }}"
           class="md-btn {{ $showInactive ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="font-size:12px;">
            {{ $showInactive ? '🔓 Hiding disabled' : '🔓 Show disabled' }}
        </a>
        @endif
        <a href="{{ route('employees.create') }}" class="md-btn md-btn--primary">+ New Employee Profile</a>
</div>

<style>
.filter-chip-group { display: flex; flex-wrap: wrap; gap: 6px; max-width: 480px; }
.filter-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 13px; border-radius: 999px;
    background: var(--md-surface-container-high);
    border: 1px solid var(--md-outline-variant);
    color: var(--md-on-surface);
    font-size: 12.5px; cursor: pointer; user-select: none;
    transition: background .15s, border-color .15s, color .15s;
}
.filter-chip:hover { border-color: var(--md-primary); }
.filter-chip input[type="radio"] {
    position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none;
}
.filter-chip input[type="radio"]:checked ~ span,
.filter-chip:has(input[type="radio"]:checked) {
    background: var(--md-primary); color: var(--md-on-primary); border-color: var(--md-primary);
}
.filter-group-label {
    display: flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600;
    color: var(--md-on-surface-variant); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .3px;
}
</style>

<form method="GET" style="margin-bottom:16px;" id="employeeFilterForm">
    @php($activeFilterCount = collect([$positionId, $unitId, $gender, $filterSubjectCodeId])->filter()->count())
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" name="q" class="md-input" style="max-width:280px;" placeholder="Search name or pay no…" value="{{ request('q') }}">
        @if($showInactive) <input type="hidden" name="show_inactive" value="1"> @endif

        <button type="button" id="toggleFiltersBtn" class="md-btn md-btn--outlined" style="font-size:12px;" onclick="toggleCustomFilters()">
            🔍 Filters
            @if($activeFilterCount > 0)
                <span class="md-badge md-badge--info" style="margin-left:4px;">{{ $activeFilterCount }} active</span>
            @endif
        </button>
    </div>

    <div id="customFiltersPanel" style="display:{{ $activeFilterCount > 0 ? 'flex' : 'none' }};gap:22px;margin-top:14px;padding:16px;background:var(--md-surface-container);border-radius:var(--md-shape-sm);flex-wrap:wrap;">

        <div>
            <div class="filter-group-label">🏷️ Position</div>
            <div class="filter-chip-group">
                <label class="filter-chip">
                    <input type="radio" name="position_id" value="" onchange="this.form.submit()" {{ ! $positionId ? 'checked' : '' }}>
                    <span>✨ All</span>
                </label>
                @foreach($filterPositions as $pos)
                    <label class="filter-chip">
                        <input type="radio" name="position_id" value="{{ $pos->id }}" onchange="this.form.submit()" {{ (string) $positionId === (string) $pos->id ? 'checked' : '' }}>
                        <span>🔹 {{ $pos->title }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <div class="filter-group-label">🏥 Unit</div>
            <div class="filter-chip-group">
                <label class="filter-chip">
                    <input type="radio" name="unit_id" value="" onchange="this.form.submit()" {{ ! $unitId ? 'checked' : '' }}>
                    <span>✨ All</span>
                </label>
                @foreach($filterUnits as $u)
                    <label class="filter-chip">
                        <input type="radio" name="unit_id" value="{{ $u->id }}" onchange="this.form.submit()" {{ (string) $unitId === (string) $u->id ? 'checked' : '' }}>
                        <span>📍 {{ $u->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <div class="filter-group-label">🚻 Gender</div>
            <div class="filter-chip-group">
                <label class="filter-chip">
                    <input type="radio" name="gender" value="" onchange="this.form.submit()" {{ ! $gender ? 'checked' : '' }}>
                    <span>✨ All</span>
                </label>
                @foreach($filterGenders as $g)
                    @php($genderIcon = ['M' => '👨', 'F' => '👩', 'O' => '⚧️'][$g] ?? '🔹')
                    <label class="filter-chip">
                        <input type="radio" name="gender" value="{{ $g }}" onchange="this.form.submit()" {{ (string) $gender === (string) $g ? 'checked' : '' }}>
                        <span>{{ $genderIcon }} {{ ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$g] ?? $g }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        @if($filterSubjectCodes->count() > 1)
        <div>
            <div class="filter-group-label">📁 Subject Code</div>
            <div class="filter-chip-group">
                <label class="filter-chip">
                    <input type="radio" name="subject_code_id" value="" onchange="this.form.submit()" {{ ! $filterSubjectCodeId ? 'checked' : '' }}>
                    <span>✨ All</span>
                </label>
                @foreach($filterSubjectCodes as $sc)
                    <label class="filter-chip">
                        <input type="radio" name="subject_code_id" value="{{ $sc->id }}" onchange="this.form.submit()" {{ (string) $filterSubjectCodeId === (string) $sc->id ? 'checked' : '' }}>
                        <span>📁 {{ $sc->code }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        @endif

        <div style="display:flex;align-items:flex-end;gap:8px;">
            <button type="submit" class="md-btn md-btn--primary" style="font-size:12px;">Apply</button>
            @if($activeFilterCount > 0)
                <a href="{{ route('employees.index', array_filter(['q' => request('q'), 'show_inactive' => $showInactive ? 1 : null])) }}" class="md-btn md-btn--text" style="font-size:12px;">Clear all filters</a>
            @endif
        </div>
    </div>
</form>

<script>
function toggleCustomFilters() {
    var panel = document.getElementById('customFiltersPanel');
    panel.style.display = (panel.style.display === 'none') ? 'flex' : 'none';
}
</script>

<div class="md-card">
    <div class="md-table-wrap">
        <table class="md-table">
            <thead>
                <tr><th>Name</th><th>Pay No</th><th>Subject Code</th><th>🏷️ Position</th><th>Unit</th><th>Gender</th><th>Email</th><th>WhatsApp</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($employees as $e)
                <tr style="{{ !$e->is_active ? 'opacity:.5;' : '' }}">
                    <td>{{ $e->display_name }}</td>
                    <td>{{ $e->pay_no ?? '—' }}</td>
                    <td>{{ $e->subjectCode->code ?? '—' }}</td>
                    <td>{{ $e->position->title ?? '—' }}</td>
                    <td>{{ $e->unit->name ?? '—' }}</td>
                    <td>{{ ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$e->gender] ?? $e->gender }}</td>
                    <td>{{ $e->email ?? '—' }}</td>
                    <td>{{ $e->whatsapp_mobile }}</td>
                    <td>{!! $e->is_active ? '<span class="md-badge md-badge--success">Active</span>' : '<span class="md-badge md-badge--neutral">Inactive</span>' !!}</td>
                    <td class="md-table__actions">
                        <a href="{{ route('employees.edit', $e) }}" class="md-btn md-btn--icon" title="Edit">&#9998;</a>
                        <a href="{{ route('employee-increments.index', $e) }}" class="md-btn md-btn--icon" title="Increment History">📈</a>
                        <a href="{{ route('employee-grades.index', $e) }}" class="md-btn md-btn--icon" title="Grade History">🎖️</a>
                        <a href="{{ route('employee-qualifications.index', $e) }}" class="md-btn md-btn--icon" title="Qualifications">🎓</a>
                        <a href="{{ route('employee-exam-records.index', $e) }}" class="md-btn md-btn--icon" title="Exam Records">📝</a>
                        <a href="{{ route('employee-interdictions.index', $e) }}" class="md-btn md-btn--icon" title="Interdiction Records">⚖️</a>
                        <a href="{{ route('employee-leave-records.index', $e) }}" class="md-btn md-btn--icon" title="Leave Records">🏖️</a>
                        <a href="{{ route('employee-confirmation.edit', $e) }}" class="md-btn md-btn--icon" title="Confirmation in Service">📋</a>
                        <x-disable-toggle :record="$e" toggle-route="employees.toggle" label="employee" :require-reason="true" :small="true" />
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="md-table__empty">No employee profiles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $employees->links('vendor.pagination.material') }}</div>
</div>
@endsection
