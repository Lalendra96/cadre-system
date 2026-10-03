<div style="overflow-x: auto;">
    <table class="mini-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recentAudit as $event)
                <tr>
                    <td>{{ optional($event->created_at)->format('d M H:i') }}</td>
                    <td>{{ $event->user?->name ?? 'System' }}</td>
                    <td>{{ ucfirst($event->action) }}</td>
                    <td>{{ $event->description ?: class_basename($event->auditable_type) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No recent audit events.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
