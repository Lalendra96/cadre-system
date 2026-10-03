@extends('layouts.app')

@section('title', 'Car Pass Management')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px;">
        <div>
            <h2 class="md-headline-sm">🚗 Car Pass Management</h2>
            <p class="md-body-sm">Governed employee vehicle-pass preparation, approval, issue, expiry and revocation.</p>
        </div>
        @if ($canPrepare)
            <a href="{{ route('car-passes.create') }}" class="md-btn md-btn--filled">+ New Car Pass</a>
        @endif
    </div>

    @if (session('success'))
        <div class="md-card" style="padding:12px;margin-bottom:14px;background:#e8f5e9;">{{ session('success') }}</div>
    @endif

    <div class="workforce-panel" style="margin-bottom:16px;">
        <div class="panel-title">Operational responsibility</div>
        <div class="md-body-sm" style="margin-top:8px;">
            @if ($assignment && $assignment->subjectOfficer)
                Responsible Subject Officer: <strong>{{ $assignment->subjectOfficer->name }}</strong>
                · Effective from {{ $assignment->effective_from->format('d M Y') }}
            @else
                <strong>No active Subject Officer assignment.</strong> Car Pass preparation remains read-only until Super
                Admin assigns responsibility.
            @endif
        </div>
    </div>

    <form method="GET" class="workforce-panel" style="display:flex;gap:10px;align-items:end;margin-bottom:16px;">
        <div class="md-form-group" style="min-width:220px;">
            <label class="md-label">Status</label>
            <select class="md-input" name="status">
                <option value="">All statuses</option>
                @foreach (['draft' => 'Draft', 'pending_approval' => 'Pending Approval', 'approved' => 'Approved', 'rejected' => 'Returned', 'issued' => 'Issued', 'revoked' => 'Revoked'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="md-btn md-btn--outlined">Filter</button>
    </form>

    <div class="workforce-panel">
        <div style="overflow:auto;">
            <table class="md-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Employee</th>
                        <th>Post</th>
                        <th>Vehicle</th>
                        <th>Validity</th>
                        <th>Status</th>
                        <th>Prepared By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($passes as $pass)
                        <tr>
                            <td>{{ $pass->reference_no }}</td>
                            <td>{{ $pass->employee_name_snapshot }}</td>
                            <td>{{ $pass->position_snapshot ?: '—' }}</td>
                            <td>{{ $pass->vehicle_registration_no }}<br><span
                                    class="md-body-sm">{{ ucwords(str_replace('_', ' ', $pass->vehicle_type)) }}</span>
                            </td>
                            <td>{{ $pass->valid_from?->format('d M Y') }} → {{ $pass->valid_to?->format('d M Y') }}</td>
                            <td><strong>{{ ucwords(str_replace('_', ' ', $pass->status)) }}</strong></td>
                            <td>{{ $pass->preparer?->name ?: '—' }}</td>
                            <td><a class="md-btn md-btn--text" href="{{ route('car-passes.show', $pass) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:28px;">No Car Pass records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px;">{{ $passes->links() }}</div>
    </div>
@endsection
