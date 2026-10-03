<div class="cadre-kpi-grid" aria-label="Dashboard key indicators">
    @foreach ($kpis as $kpi)
        <article class="cadre-kpi-card cadre-kpi-card--{{ $kpi['tone'] ?? 'info' }}">
            <div class="cadre-kpi-card__icon" aria-hidden="true">{{ $kpi['icon'] ?? '•' }}</div>
            <div class="cadre-kpi-card__body">
                <span class="cadre-kpi-card__label">{{ $kpi['label'] }}</span>
                <strong
                    class="cadre-kpi-card__value">{{ number_format($kpi['value']) }}{{ $kpi['suffix'] ?? '' }}</strong>
                <small>{{ $kpi['hint'] ?? '' }}</small>
            </div>
        </article>
    @endforeach
</div>
