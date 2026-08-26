@extends('layouts.app')
@section('title', 'Export Audit Log')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <h2 class="md-headline-sm">Export & Download Audit Log</h2>
</div>
<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <select name="type" class="md-field__input" style="height:36px;max-width:200px;" onchange="this.form.submit()">
        <option value="">All types</option>
        @foreach($types as $t)
            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
        @endforeach
    </select>
    <input type="date" name="from" class="md-field__input" style="height:36px;max-width:160px;" value="{{ request('from') }}" onchange="this.form.submit()">
</form>
<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead><tr><th>User</th><th>Type</th><th>File</th><th>Resource</th><th>IP</th><th>Time</th></tr></thead>
            <tbody>
                @forelse($logs as $l)
                <tr>
                    <td class="md-label-md">{{ $l->user->name ?? 'Unknown' }}</td>
                    <td><span class="md-badge md-badge--info" style="font-size:10px;">{{ $l->export_type }}</span></td>
                    <td class="md-body-sm">{{ $l->filename }}</td>
                    <td class="md-body-sm">{{ $l->resource_type }}{{ $l->resource_id ? '#'.$l->resource_id : '' }}</td>
                    <td class="md-body-sm"><code>{{ $l->ip_address }}</code></td>
                    <td class="md-body-sm">{{ $l->exported_at->format('d M Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="md-table__empty">No export events recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $logs->links('vendor.pagination.material') }}</div>
</div>
@endsection
