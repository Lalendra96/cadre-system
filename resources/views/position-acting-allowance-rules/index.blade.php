@extends('layouts.app')
@section('title', 'Acting Allowance Rules')
@section('content')

<div style="margin-bottom:16px;">
    <h2 class="md-headline-sm">Acting Allowance Rules</h2>
    <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
        Configure how the acting allowance is calculated when an employee acts in each position.
        A position with no rule shows as "Not configured" wherever an acting appointment's allowance would be displayed.
    </p>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
@endif
@if(session('error'))
<div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">{{ session('error') }}</div>
@endif

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Rule</th>
                    <th style="text-align:right;">Rate</th>
                    <th style="text-align:center;">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($positions as $p)
                @php($rule = $p->actingAllowanceRule)
                <tr>
                    <td class="md-label-md">{{ $p->title }}</td>
                    <td class="md-body-sm">
                        @if($rule)
                            {{ \App\Models\PositionActingAllowanceRule::RULE_TYPE_LABELS[$rule->rule_type] ?? $rule->rule_type }}
                        @else
                            <span style="color:var(--md-on-surface-variant);">Not configured</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        @if($rule && $rule->rule_type === 'flat_amount')
                            Rs. {{ number_format($rule->flat_amount, 2) }}
                        @elseif($rule)
                            {{ $rule->percentage }}%
                        @else
                            —
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($rule && $rule->is_active)
                            <span class="md-badge md-badge--success">Active</span>
                        @elseif($rule)
                            <span class="md-badge md-badge--neutral">Disabled</span>
                        @else
                            <span class="md-badge md-badge--neutral">—</span>
                        @endif
                    </td>
                    <td>
                        @if($rule)
                            <div style="display:flex;gap:4px;align-items:center;">
                                <a href="{{ route('position-acting-allowance-rules.edit', $p) }}" class="md-btn md-btn--icon" title="Edit">✏️</a>
                                <x-disable-toggle :record="$rule" toggle-route="position-acting-allowance-rules.toggle"
                                    :route-params="[$p]" label="acting allowance rule" :require-reason="false" :small="true" />
                            </div>
                        @else
                            <a href="{{ route('position-acting-allowance-rules.create', $p) }}" class="md-btn md-btn--outlined" style="font-size:11px;">+ Configure</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
