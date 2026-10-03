<section class="cadre-dashboard-panel">
    <div class="cadre-dashboard-panel__head">
        <div>
            <h3>{{ $title ?? 'Cadre Availability Watchlist' }}</h3>
            <p>{{ $subtitle ?? 'System-calculated comparison of approved cadre and current recorded availability.' }}
            </p>
        </div>
        @if (Route::has('reports.summary'))
            <a class="cadre-panel-link" href="{{ route('reports.summary') }}">View detailed report →</a>
        @endif
    </div>

    <div class="cadre-table-wrap">
        <table class="cadre-dashboard-table">
            <thead>
                <tr>
                    <th>Position / Cadre</th>
                    <th>Approved</th>
                    <th>Recorded</th>
                    <th>Gap</th>
                    <th>Fill rate</th>
                    <th>Indicator</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($topVacancyPositions as $row)
                    @php
                        $risk =
                            $row['gap'] <= 0
                                ? 'good'
                                : ($row['fill_rate'] < 75
                                    ? 'high'
                                    : ($row['fill_rate'] < 90
                                        ? 'medium'
                                        : 'watch'));
                    @endphp
                    <tr>
                        <td><strong>{{ $row['position'] }}</strong></td>
                        <td>{{ number_format($row['approved']) }}</td>
                        <td>{{ number_format($row['available']) }}</td>
                        <td>{{ number_format($row['gap']) }}</td>
                        <td>{{ number_format($row['fill_rate'], 1) }}%</td>
                        <td><span class="cadre-status cadre-status--{{ $risk }}">{{ ucfirst($risk) }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="cadre-empty-cell">No approved cadre information is available for the
                            current period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="cadre-panel-footnote">
        Gap and fill-rate values are analytical indicators only. Validate source records before administrative action.
    </div>
</section>
