@extends('layouts.app')
@section('title', 'System-Calculated Retirement Projections')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <h2 class="md-headline-sm">Retirement Projections</h2>
    <a href="{{ route('reports.retirement-projections.pdf', ['months'=>$horizon]) }}" class="md-btn md-btn--outlined">📄 PDF</a>
    <form method="GET" style="display:flex;align-items:center;gap:8px;">
        <label class="md-field__label" style="margin:0;">Horizon:</label>
        <select name="months" class="md-field__input" style="height:36px;max-width:160px;" onchange="this.form.submit()">
            @foreach([6=>'6 months',12=>'12 months',24=>'24 months',36=>'36 months'] as $m => $l)
                <option value="{{ $m }}" {{ $m == $horizon ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </form>
</div>

@if($employees->isEmpty())
<div class="md-card md-card--elevated" style="padding:40px;text-align:center;color:var(--md-on-surface-variant);">
    No employees are system-projected within the selected {{ $horizon }}-month retirement horizon, or the required date-of-birth data is not recorded.
    <br><br>
    <a href="{{ route('employees.index') }}" class="md-btn md-btn--tonal">Update Employee Records</a>
</div>
@else
<div style="display:flex;flex-direction:column;gap:12px;">
    @foreach(['overdue'=>['label'=>'Projected date passed — verification required','color'=>'var(--md-error)','badge'=>'md-badge--critical'], 'critical'=>['label'=>'Within 6 months','color'=>'var(--md-error)','badge'=>'md-badge--critical'], 'soon'=>['label'=>'7–12 months','color'=>'var(--md-warning)','badge'=>'md-badge--warning'], 'upcoming'=>['label'=>'13 months onwards','color'=>'var(--md-primary)','badge'=>'md-badge--info']] as $status => $meta)
        @php($group = $employees->where('status', $status))
        @if($group->isNotEmpty())
        <div class="md-card md-card--elevated">
            <div class="md-card__header" style="border-left:4px solid {{ $meta['color'] }};padding-left:16px;">
                <span class="md-title-md" style="color:{{ $meta['color'] }};">{{ $meta['label'] }} ({{ $group->count() }})</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="md-table">
                    <thead><tr><th>Name</th><th>Pay No.</th><th>Position</th><th>DOB</th><th>Age Now</th><th>System-Projected Retirement Date</th><th>Months Left</th></tr></thead>
                    <tbody>
                        @foreach($group as $e)
                        <tr>
                            <td class="md-label-md">{{ $e->name }}</td>
                            <td class="md-body-sm">{{ $e->pay_no }}</td>
                            <td class="md-body-sm">{{ $e->position }}</td>
                            <td class="md-body-sm">{{ $e->dob }}</td>
                            <td style="text-align:center;">{{ $e->age }}</td>
                            <td class="md-body-sm">{{ $e->retire_date }}</td>
                            <td style="text-align:center;">
                                @if($e->months_left < 0)
                                    <span class="md-badge md-badge--critical">Projected date passed</span>
                                @else
                                    <span class="md-badge {{ $meta['badge'] }}">{{ $e->months_left }}m</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endforeach
</div>
@endif
@endsection
