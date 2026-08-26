@extends('layouts.app')
@section('title', 'IP Allowlist')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <div>
        <h2 class="md-headline-sm">IP Allowlist</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">Your current IP: <code style="background:var(--md-surface-container-high);padding:2px 8px;border-radius:4px;color:var(--md-primary);">{{ $yourIp }}</code></p>
    </div>
    <a href="{{ route('ip-allowlist.create') }}" class="md-btn md-btn--filled">+ Add Range</a>
</div>
<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead><tr><th>CIDR Range</th><th>Description</th><th>Status</th><th>Added</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($entries as $e)
                <tr>
                    <td><code style="font-size:13px;color:var(--md-primary);">{{ $e->cidr }}</code></td>
                    <td class="md-body-sm">{{ $e->description ?: '—' }}</td>
                    <td><span class="md-badge {{ $e->is_active ? 'md-badge--success' : 'md-badge--neutral' }}">{{ $e->is_active ? 'Active' : 'Disabled' }}</span></td>
                    <td class="md-body-sm">{{ $e->created_at->format('d M Y') }} · {{ $e->creator->name ?? '—' }}</td>
                    <td>
                        <x-disable-toggle :record="$e" toggle-route="ip-allowlist.destroy" label="IP range" :require-reason="false" :small="true" />
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="md-table__empty">No IP ranges configured. All IPs are blocked when the allowlist is enabled with no entries.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
