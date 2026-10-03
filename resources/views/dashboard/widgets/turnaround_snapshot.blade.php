<div style="display: grid; gap: 14px;">
    <div>
        <strong style="font-size: 12px;">Car Pass Requests</strong>
        <div class="status-pills" style="margin-top: 8px;">
            @forelse ($workflowStatusMix['car_pass'] as $status => $total)
                <div class="status-pill">
                    <strong>{{ number_format($total) }}</strong>
                    <span>{{ ucwords(str_replace('_', ' ', $status)) }}</span>
                </div>
            @empty
                <span>No car-pass workflow data.</span>
            @endforelse
        </div>
    </div>
    <div>
        <strong style="font-size: 12px;">Service Letters</strong>
        <div class="status-pills" style="margin-top: 8px;">
            @forelse ($workflowStatusMix['service_letter'] as $status => $total)
                <div class="status-pill">
                    <strong>{{ number_format($total) }}</strong>
                    <span>{{ ucwords(str_replace('_', ' ', $status)) }}</span>
                </div>
            @empty
                <span>No service-letter workflow data.</span>
            @endforelse
        </div>
    </div>
</div>
