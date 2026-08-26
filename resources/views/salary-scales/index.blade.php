@extends('layouts.app')
@section('title', 'Salary Scales')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Salary Scales</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Official MoH salary scale codes referenced by employee profiles.
        </p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        @if($canSeeInactive)
        <a href="{{ route('salary-scales.index', array_merge(request()->query(), ['show_inactive' => $showInactive ? 0 : 1])) }}"
           class="md-btn {{ $showInactive ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="font-size:12px;">
            {{ $showInactive ? '🔓 Hiding disabled' : '🔓 Show disabled' }}
        </a>
        @endif
        <a href="{{ route('salary-scales.create') }}" class="md-btn md-btn--filled">+ New Salary Scale</a>
    </div>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ✓ {{ session('success') }}
</div>
@endif

<form method="GET" style="margin-bottom:16px;max-width:320px;">
    <input type="text" name="q" value="{{ request('q') }}"
           class="md-field__input" placeholder="Search by code or name…"
           onchange="this.form.submit()">
</form>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th style="text-align:right;">Min Salary</th>
                    <th style="text-align:right;">Max Salary</th>
                    <th style="text-align:right;">Increment</th>
                    <th style="text-align:right;">Employees</th>
                    <th style="text-align:center;">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaryScales as $s)
                <tr style="{{ !$s->is_active ? 'opacity:.5;' : '' }}">
                    <td class="md-label-md">{{ $s->code }}</td>
                    <td class="md-body-sm">{{ $s->name }}</td>
                    <td style="text-align:right;">{{ $s->min_salary ? 'Rs. ' . number_format($s->min_salary, 2) : '—' }}</td>
                    <td style="text-align:right;">{{ $s->max_salary ? 'Rs. ' . number_format($s->max_salary, 2) : '—' }}</td>
                    <td style="text-align:right;">{{ $s->increment_amount ? 'Rs. ' . number_format($s->increment_amount, 2) : '—' }}</td>
                    <td style="text-align:right;">{{ $s->employees_count }}</td>
                    <td style="text-align:center;">
                        @if($s->is_active)
                            <span class="md-badge md-badge--success">Active</span>
                        @else
                            <span class="md-badge md-badge--neutral">Disabled</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;align-items:center;">
                            <a href="{{ route('salary-scales.edit', $s) }}" class="md-btn md-btn--icon" title="Edit">✏️</a>
                            <x-disable-toggle :record="$s" toggle-route="salary-scales.toggle"
                                label="salary scale" :require-reason="false" :small="true" />
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="md-table__empty">
                        No salary scales configured yet.
                        <a href="{{ route('salary-scales.create') }}">Add the first one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $salaryScales->links('vendor.pagination.material') }}</div>
</div>

@endsection
