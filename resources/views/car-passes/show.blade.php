@extends('layouts.app')

@section('title', 'Car Pass ' . $carPass->reference_no)

@section('content')
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:18px;">
        <div>
            <h2 class="md-headline-sm">Car Pass {{ $carPass->reference_no }}</h2>
            <p class="md-body-sm">Status: <strong>{{ ucwords(str_replace('_', ' ', $carPass->status)) }}</strong></p>
        </div>
        <a href="{{ route('car-passes.index') }}" class="md-btn md-btn--text">← Back</a>
    </div>

    @if (session('success'))
        <div class="md-card" style="padding:12px;margin-bottom:14px;background:#e8f5e9;">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="md-card" style="padding:14px;margin-bottom:16px;background:#ffebee;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:minmax(0,1.4fr) minmax(300px,.8fr);gap:16px;align-items:start;">
        <div class="workforce-panel">
            <div class="panel-title">Official Record Details</div>
            <table class="md-table" style="width:100%;margin-top:12px;">
                <tbody>
                    <tr>
                        <th>Employee</th>
                        <td>{{ $carPass->employee_name_snapshot }}</td>
                    </tr>
                    <tr>
                        <th>Pay No</th>
                        <td>{{ $carPass->pay_no_snapshot ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Post</th>
                        <td>{{ $carPass->position_snapshot ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Unit</th>
                        <td>{{ $carPass->unit_snapshot ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Pass Format</th>
                        <td>{{ $carPass->template_name_snapshot }} · v{{ $carPass->template_version_snapshot }}</td>
                    </tr>
                    <tr>
                        <th>Vehicle Registration</th>
                        <td><strong>{{ $carPass->vehicle_registration_no }}</strong></td>
                    </tr>
                    <tr>
                        <th>Vehicle Type</th>
                        <td>{{ ucwords(str_replace('_', ' ', $carPass->vehicle_type)) }}</td>
                    </tr>
                    <tr>
                        <th>Validity</th>
                        <td>{{ $carPass->valid_from?->format('d M Y') }} → {{ $carPass->valid_to?->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <th>Purpose</th>
                        <td>{{ $carPass->purpose ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Prepared By</th>
                        <td>{{ $carPass->preparer?->name ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Reviewer</th>
                        <td>{{ $carPass->reviewer?->name ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Decision Note</th>
                        <td>{{ $carPass->decision_note ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th>Integrity Seal</th>
                        <td style="word-break:break-all;">{{ $carPass->content_hash ?: 'Not sealed until approval' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="workforce-panel">
            <div class="panel-title">Pass Format Preview</div>
            <img src="{{ route('car-passes.snapshot-image', $carPass) }}" alt="Pass template"
                style="width:100%;max-height:320px;object-fit:contain;margin-top:12px;background:#f5f7fa;border-radius:8px;">
            <div class="md-card" style="padding:12px;margin-top:12px;">
                <strong>{{ $carPass->employee_name_snapshot }}</strong><br>
                <span class="md-body-sm">{{ $carPass->position_snapshot }}</span><br><br>
                Vehicle: <strong>{{ $carPass->vehicle_registration_no }}</strong><br>
                Type: {{ ucwords(str_replace('_', ' ', $carPass->vehicle_type)) }}<br>
                Valid to: {{ $carPass->valid_to?->format('d M Y') }}
            </div>
        </div>
    </div>

    <div class="workforce-panel" style="margin-top:16px;">
        <div class="panel-title">Workflow Actions</div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;align-items:flex-start;">
            @if ($canPrepare && $carPass->prepared_by === auth()->id() && $carPass->isMutable())
                <a href="{{ route('car-passes.edit', $carPass) }}" class="md-btn md-btn--outlined">Edit Draft</a>
                <form method="POST" action="{{ route('car-passes.submit', $carPass) }}">
                    @csrf
                    <button class="md-btn md-btn--filled">Submit for Approval</button>
                </form>
            @endif

            @if ($canApprove && $carPass->status === 'pending_approval')
                <form method="POST" action="{{ route('car-passes.approve', $carPass) }}"
                    style="display:flex;gap:8px;align-items:center;">
                    @csrf
                    <input name="decision_note" class="md-input" placeholder="Approval note (optional)">
                    <button class="md-btn md-btn--filled">Approve</button>
                </form>
                <form method="POST" action="{{ route('car-passes.reject', $carPass) }}"
                    style="display:flex;gap:8px;align-items:center;">
                    @csrf
                    <input name="decision_note" class="md-input" required minlength="5" placeholder="Reason required">
                    <button class="md-btn md-btn--outlined">Return / Reject</button>
                </form>
            @endif

            @if ($canPrepare && $carPass->status === 'approved')
                <form method="POST" action="{{ route('car-passes.issue', $carPass) }}">
                    @csrf
                    <button class="md-btn md-btn--filled">Issue Pass</button>
                </form>
            @endif

            @if (in_array($carPass->status, ['approved', 'issued', 'revoked'], true))
                <a href="{{ route('car-passes.print', $carPass) }}" target="_blank" class="md-btn md-btn--outlined">Print /
                    Export Pass</a>
            @endif

            @if ((auth()->user()->isSuperAdmin() || $canApprove) && $carPass->status === 'issued')
                <form method="POST" action="{{ route('car-passes.revoke', $carPass) }}"
                    style="display:flex;gap:8px;align-items:center;">
                    @csrf
                    <input name="revocation_reason" class="md-input" required minlength="5"
                        placeholder="Revocation reason">
                    <button class="md-btn md-btn--outlined">Revoke</button>
                </form>
            @endif
        </div>
    </div>
@endsection
