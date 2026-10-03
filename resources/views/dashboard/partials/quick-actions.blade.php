<section class="cadre-dashboard-panel cadre-quick-panel">
    <div class="cadre-dashboard-panel__head">
        <div>
            <h3>Quick Actions</h3>
            <p>Role-appropriate shortcuts. Access is still enforced by the destination workflow.</p>
        </div>
    </div>
    <div class="cadre-quick-grid">
        @foreach ($actions as $action)
            @if (Route::has($action['route']))
                <a href="{{ route($action['route']) }}"
                    class="cadre-quick-action cadre-quick-action--{{ $action['tone'] ?? 'blue' }}">
                    <span aria-hidden="true">{{ $action['icon'] ?? '→' }}</span>
                    <div>
                        <strong>{{ $action['label'] }}</strong>
                        <small>{{ $action['hint'] ?? '' }}</small>
                    </div>
                    <b aria-hidden="true">→</b>
                </a>
            @endif
        @endforeach
    </div>
</section>
