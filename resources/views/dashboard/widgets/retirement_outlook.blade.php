@php
    $max = max((int) ($retirementYears->max('value') ?? 0), 1);
@endphp
<div class="metric-list">
    @foreach ($retirementYears as $row)
        <div class="metric-row">
            <span>{{ $row['label'] }}</span>
            <div class="metric-track" aria-hidden="true">
                <div class="metric-fill" style="width: {{ min(100, round(($row['value'] / $max) * 100, 1)) }}%;"></div>
            </div>
            <span class="metric-value">{{ number_format($row['value']) }}</span>
        </div>
    @endforeach
</div>
