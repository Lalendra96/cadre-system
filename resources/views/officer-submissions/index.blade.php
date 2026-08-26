@extends('layouts.app')
@section('title', 'Officer Submission Rate')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h2 class="md-headline-sm">Officer Monthly Submission Rate</h2>
</div>

<form method="GET" style="display:flex;gap:12px;margin-bottom:20px;">
    <select name="year" class="md-field__input" style="height:40px;max-width:130px;" onchange="this.form.submit()">
        @for($y = now()->year; $y >= now()->year - 3; $y--)
            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
    </select>
    <select name="month" class="md-field__input" style="height:40px;max-width:160px;" onchange="this.form.submit()">
        @for($m = 1; $m <= 12; $m++)
            <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>
        @endfor
    </select>
</form>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div class="md-card md-card--elevated" style="padding:20px;border-left:4px solid var(--md-primary);">
        <div class="md-label-sm" style="color:var(--md-on-surface-variant);">Overall Submission Rate</div>
        <div class="md-headline-md" style="color:var(--md-primary);margin-top:6px;">{{ $submissionRate }}%</div>
    </div>
    <div class="md-card md-card--elevated" style="padding:20px;border-left:4px solid var(--md-success);">
        <div class="md-label-sm" style="color:var(--md-on-surface-variant);">Submitted</div>
        <div class="md-headline-md" style="color:var(--md-success);margin-top:6px;">{{ $submittedSlots }}</div>
    </div>
    <div class="md-card md-card--elevated" style="padding:20px;border-left:4px solid var(--md-error);">
        <div class="md-label-sm" style="color:var(--md-on-surface-variant);">Missing</div>
        <div class="md-headline-md" style="color:var(--md-error);margin-top:6px;">{{ $totalSlots - $submittedSlots }}</div>
    </div>
</div>

<div class="md-card md-card--elevated">
    <div class="md-card__header">
        <span class="md-title-md">Position → Subject Code → Officer — Last 6 Months</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th style="min-width:160px;">Position</th>
                    <th style="min-width:180px;">Subject Code</th>
                    <th style="min-width:160px;">Assigned Officer(s)</th>
                    @foreach($periods as $p)
                        <th style="text-align:center;min-width:90px;">{{ $p['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($positionData as $pos)
                    @foreach($pos->codes as $ci => $code)
                        <tr>
                            @if($ci === 0)
                                <td rowspan="{{ count($pos->codes) }}" style="font-weight:500;vertical-align:top;padding-top:14px;border-right:1px solid var(--md-outline-variant);">{{ $pos->title }}</td>
                            @endif
                            <td style="font-family:monospace;font-size:13px;">{{ $code->code }}<br><span style="font-size:11px;color:var(--md-on-surface-variant);">{{ $code->name }}</span></td>
                            <td style="font-size:13px;color:var(--md-on-surface-variant);">{{ $code->officers }}</td>
                            @foreach($code->periods as $period)
                                <td style="text-align:center;padding:8px;">
                                    @if($period['status'] === 'submitted')
                                        <span class="md-badge md-badge--success" title="Submitted {{ optional($period['submitted_at'])->format('d M Y') }}">
                                            ✓ {{ $period['total'] }}
                                        </span>
                                    @elseif($period['status'] === 'carried')
                                        @php($fromLabel = \DateTime::createFromFormat('!m', $period['carried_from_month'])->format('M') . ' ' . $period['carried_from_year'])
                                        <span class="md-badge md-badge--info" title="Carried forward from {{ $fromLabel }}">
                                            ↻ {{ $period['total'] }}
                                        </span>
                                    @elseif($period['status'] === 'no_officer')
                                        <span class="md-badge md-badge--neutral">—</span>
                                    @else
                                        <span class="md-badge md-badge--critical">Missing</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="{{ 3 + count($periods) }}" class="md-table__empty">No positions with subject codes found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="display:flex;gap:16px;padding:12px 16px;border-top:1px solid var(--md-outline-variant);flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--md-on-surface-variant);">
            <span class="md-badge md-badge--success">✓ N</span> Submitted for this month
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--md-on-surface-variant);">
            <span class="md-badge md-badge--info">↻ N</span> Carried forward from last submission
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--md-on-surface-variant);">
            <span class="md-badge md-badge--critical">Missing</span> No historical data exists
        </div>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--md-on-surface-variant);">
            <span class="md-badge md-badge--neutral">—</span> No officer assigned
        </div>
    </div>
</div>
@endsection
