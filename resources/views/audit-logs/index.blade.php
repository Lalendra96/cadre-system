@extends('layouts.app')
@section('title', 'Audit Log')
@section('content')
<h1 class="md-h2" style="margin-bottom:16px;">Audit Log</h1>

<form method="GET" class="md-flex md-gap-16" style="margin-bottom:16px;">
    <select name="type" class="md-select" style="max-width:220px;" onchange="this.form.submit()">
        <option value="">All record types</option>
        @foreach($modelTypes as $class => $label)
            <option value="{{ $class }}" {{ request('type') === $class ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <select name="action" class="md-select" style="max-width:160px;" onchange="this.form.submit()">
        <option value="">All actions</option>
        <option value="created" {{ request('action') === 'created' ? 'selected' : '' }}>Created</option>
        <option value="updated" {{ request('action') === 'updated' ? 'selected' : '' }}>Updated</option>
        <option value="deleted" {{ request('action') === 'deleted' ? 'selected' : '' }}>Deleted</option>
    </select>
</form>

<div class="md-card">
    <div class="md-table-wrap">
        <table class="md-table">
            <thead>
                <tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Description</th><th>Details</th></tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>
                        @php($badge = ['created' => 'success', 'updated' => 'primary', 'deleted' => 'error'][$log->action] ?? 'neutral')
                        <span class="md-badge md-badge--{{ $badge }}">{{ $log->action }}</span>
                    </td>
                    <td>{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                    <td>{{ $log->description ?? '—' }}</td>
                    <td>
                        @if($log->old_values || $log->new_values)
                            <details>
                                <summary class="md-caption" style="cursor:pointer;">View changes</summary>
                                <div class="md-mt-8">
                                    @if($log->old_values)
                                        <div class="md-caption"><strong>Before:</strong> {{ json_encode($log->old_values) }}</div>
                                    @endif
                                    @if($log->new_values)
                                        <div class="md-caption"><strong>After:</strong> {{ json_encode($log->new_values) }}</div>
                                    @endif
                                </div>
                            </details>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="md-table__empty">No changes recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $logs->links('vendor.pagination.material') }}</div>
</div>
@endsection
