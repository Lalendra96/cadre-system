@extends('layouts.app')

@section('title', 'Business Rule History')

@section('content')
<div class="page-header">
    <div>
        <a href="{{ route('governance.index') }}">
            ← Governance
        </a>

        <h1 class="page-title">
            📜 {{ $rule->name }} — Version History
        </h1>

        <p class="page-subtitle">
            Immutable snapshots of changes recorded in the Governance Register.
        </p>
    </div>
</div>

<div class="workforce-panel">
    <table class="md-table">
        <thead>
            <tr>
                <th>Version</th>
                <th>Change</th>
                <th>Changed By</th>
                <th>Date</th>
                <th>Reason</th>
                <th>Authority Reference</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($rule->versions as $version)
                <tr>
                    <td>v{{ $version->version_no }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $version->change_type)) }}</td>
                    <td>{{ $version->changedBy?->name ?? 'System / unknown' }}</td>
                    <td>{{ $version->created_at?->format('d M Y H:i') }}</td>
                    <td>{{ $version->change_reason ?: '—' }}</td>
                    <td>
                        {{ data_get($version->snapshot, 'authority_reference', '—') ?: '—' }}
                    </td>
                    <td>
                        {{
                            \App\Models\BusinessRule::STATUSES[
                                data_get($version->snapshot, 'status')
                            ]
                            ?? data_get($version->snapshot, 'status', '—')
                        }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="md-table__empty">
                        No version history is available for this rule.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
