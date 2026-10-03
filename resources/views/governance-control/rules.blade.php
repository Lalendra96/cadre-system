@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Versioned Governance Rules',
        'subtitle' =>
            'Effective-dated rules and authorities. New versions supersede rather than overwrite historical rules.',
    ])
    <div class="enterprise-section enterprise-card">
        <h2>Create next rule version</h2>
        <form class="enterprise-form" method="POST" action="{{ route('governance-control.rules.store') }}">@csrf<input
                name="rule_key" placeholder="Stable rule key e.g. RETIREMENT_AGE" required><input name="title"
                placeholder="Rule title" required><input name="authority_type" placeholder="Authority type"><input
                name="authority_reference" placeholder="Circular / service minute / order"><input type="date"
                name="effective_from" required><input type="date" name="effective_until"><input
                name="applicable_category" placeholder="Applicable employee category">
            <textarea class="span2" name="rule_definition" placeholder="Rule definition / policy logic" required></textarea>
            <textarea class="span2" name="implementation_notes" placeholder="System implementation / interpretation notes"></textarea><button class="md-btn md-btn--filled" type="submit">Create new version</button>
        </form>
    </div>
    <div class="enterprise-section enterprise-card">
        <h2>Rule history</h2>
        <table class="enterprise-table">
            <tr>
                <th>Rule</th>
                <th>Version</th>
                <th>Authority</th>
                <th>Effective</th>
                <th>Applies to</th>
                <th>State</th>
            </tr>
            @forelse($rules as $r)
                <tr>
                    <td><strong>{{ $r->rule_key }}</strong><br>{{ $r->title }}</td>
                    <td>v{{ $r->version_no }}</td>
                    <td>{{ $r->authority_type }} {{ $r->authority_reference }}</td>
                    <td>{{ $r->effective_from }} → {{ $r->effective_until ?: 'Open' }}</td>
                    <td>{{ $r->applicable_category ?: 'General' }}</td>
                    <td>{{ $r->is_active ? 'Current' : 'Historical' }}</td>
            </tr>@empty<tr>
                    <td colspan="6">No versioned rules yet.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection
