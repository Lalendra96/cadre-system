@extends('layouts.app')
@section('title', 'My Employee Record')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Employee self-service</div>
            <h1 class="page-title">My Employee Record</h1>
            <p class="page-subtitle">Review your profile, service details, documents and submitted correction requests.</p>
        </div>
    </div>
    <section class="md-card" style="padding:20px;margin-bottom:16px">
        <h2 class="md-title-md">Profile summary</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:12px">
            <div>
                <span class="md-body-sm">Name</span>
                <br>
                <strong>{{ $employee->display_name }}</strong>
            </div>
            <div>
                <span class="md-body-sm">Position</span>
                <br>
                <strong>{{ $employee->position?->title ?? '—' }}</strong>
            </div>
            <div>
                <span class="md-body-sm">Unit</span>
                <br>
                <strong>{{ $employee->unit?->name ?? '—' }}</strong>
            </div>
            <div>
                <span class="md-body-sm">Next increment</span>
                <br>
                <strong>{{ $employee->next_increment_date?->format('d M Y') ?? '—' }}</strong>
            </div>
        </div>
    </section>
    <section class="md-card" style="padding:20px">
        <h2 class="md-title-md">Correction requests</h2>
        <table class="md-table" style="width:100%;margin-top:10px">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $change)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $change->field_name)) }}</td>
                        <td>{{ ucfirst($change->status) }}</td>
                        <td>{{ $change->requested_value }}</td>
                        <td>{{ $change->reason }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No requests submitted.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
