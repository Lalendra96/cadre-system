@extends('layouts.app')
@section('title', 'Bulk Increment Builder')
@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
    <div>
        <h2 class="md-headline-sm">Bulk Increment Builder</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Select a position and prepare increment proposals for multiple employees. Each completed row is submitted for independent human approval; no increment is applied immediately.
        </p>
    </div>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ✓ {{ session('success') }}
</div>
@endif
@if($errors->any())
<div style="background:var(--md-error-container);color:var(--md-on-error-container);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

{{-- Position picker — GET, reloads the page with the employee grid --}}
<form method="GET" class="md-card md-card--elevated" style="padding:16px 20px;margin-bottom:16px;">
    <div style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
        <div class="md-field" style="max-width:360px;flex:1;">
            <label class="md-field__label">Position</label>
            <select name="position_id" class="md-field__input" onchange="this.form.submit()">
                <option value="">— Select a position —</option>
                @foreach($positions as $p)
                    <option value="{{ $p->id }}" {{ (string) $selectedPositionId === (string) $p->id ? 'selected' : '' }}>
                        {{ $p->title }}
                    </option>
                @endforeach
            </select>
        </div>
        @if($positions->isEmpty())
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                No positions with employees found under your assigned subject codes.
            </p>
        @endif
    </div>
</form>

@if($selectedPositionId && $employees->isNotEmpty())
<form method="POST" action="{{ route('bulk-increments.store') }}" class="md-card md-card--elevated">
    @csrf
    <input type="hidden" name="position_id" value="{{ $selectedPositionId }}">

    <div style="padding:14px 20px;border-bottom:1px solid var(--md-outline-variant);
                display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
            {{ $employees->count() }} employee(s). Leave a row's date blank to skip that employee.
        </span>
        <div style="display:flex;gap:8px;align-items:center;">
            <label class="md-field__label" style="margin:0;font-size:11px;">Apply date to all:</label>
            <input type="date" id="bulkDateFill" class="md-field__input" style="width:150px;height:34px;">
            <button type="button" id="applyBulkDate" class="md-btn md-btn--outlined" style="font-size:11px;">Apply</button>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Subject Code</th>
                    <th>Last Increment</th>
                    <th style="text-align:center;">New Increment Date</th>
                    <th style="text-align:center;">Amount (Rs.)</th>
                    <th>Reference No.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $i => $emp)
                @php($lastInc = $emp->incrementRecords->first())
                <tr>
                    <td class="md-label-md">
                        {{ $emp->display_name }}
                        <input type="hidden" name="employee_id[{{ $i }}]" value="{{ $emp->id }}">
                    </td>
                    <td><span class="md-badge md-badge--info">{{ $emp->subjectCode->code ?? '—' }}</span></td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">
                        {{ $lastInc?->increment_date?->format('d M Y') ?? '— none recorded —' }}
                    </td>
                    <td>
                        <input type="date" name="increment_date[{{ $i }}]" class="bulk-inc-date md-field__input" style="min-width:150px;">
                    </td>
                    <td>
                        <input type="number" name="amount[{{ $i }}]" step="0.01" min="0" class="md-field__input" style="width:120px;">
                    </td>
                    <td>
                        <input type="text" name="reference_no[{{ $i }}]" class="md-field__input" style="width:140px;" maxlength="60">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="md-card__footer" style="display:flex;justify-content:flex-end;">
        <button type="submit" class="md-btn md-btn--filled">✅ Submit All Completed Rows for Approval</button>
    </div>
</form>
@elseif($selectedPositionId)
<div class="md-card md-card--elevated" style="padding:40px;text-align:center;color:var(--md-on-surface-variant);">
    No active employees hold this position under your assigned subject codes.
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    const applyBtn  = document.getElementById('applyBulkDate');
    const fillInput = document.getElementById('bulkDateFill');
    if (!applyBtn || !fillInput) return;

    applyBtn.addEventListener('click', function () {
        if (!fillInput.value) return;
        document.querySelectorAll('.bulk-inc-date').forEach(function (input) {
            input.value = fillInput.value;
        });
    });
})();
</script>
@endpush
