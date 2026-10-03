@extends('layouts.app')
@section('title', 'Utility Bill Management')
@section('content')
    <div class="page-hero" style="margin-bottom:16px">
        <div>
            <h2 class="md-headline-sm">💡 Utility Bill Management</h2>
            <p class="md-body-sm">Monitor institutional utility accounts, due dates, outstanding balances and payment history.</p>
        </div>
        @if ($canManage)
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a class="md-btn md-btn--outlined" href="{{ route('utility-bills.accounts.create') }}">+ Utility Account</a>
                <a class="md-btn md-btn--filled" href="{{ route('utility-bills.create') }}">+ Record Bill</a>
            </div>
        @endif
    </div>

    @include('partials.governance-legal-safeguard', [
        'title' => 'Utility payment control — monitoring is not payment approval',
        'purpose' => 'Use this module to monitor official utility obligations and record verified transactions. Charts and overdue flags are operational signals; payment authority must come from the institution's approved financial process.',
        'items' => [
            'Verify the original bill/account reference before creating a bill or recording a payment.',
            'Do not record a payment from a dashboard status alone; confirm the voucher/payment evidence and authority/reference.',
            'Avoid placing bank credentials, passwords, card data or unrelated personal information in remarks.',
            'Corrections should preserve the audit trail; do not delete transactions to hide an error.',
            'Exports are official-use records and are audit logged.',
        ],
    ])

    @if (!$assignment)
        <div class="alert alert-warning">
<strong>No active Subject Officer assignment.</strong> Monitoring remains available, but operational updates are locked until Super Admin assigns responsibility.</div>
    @else
        <div class="alert alert-info">Responsible Subject Officer: <strong>{{ $assignment->subjectOfficer?->name }}</strong> · Effective {{ $assignment->effective_from?->format('d M Y') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px">
        @foreach ([
            ['Active Accounts', $summary['active_accounts']],
            ['Unpaid / Partial', $summary['unpaid_count']],
            ['Overdue', $summary['overdue_count']],
            ['Due in '.$warningDays.' Days', $summary['due_soon_count']],
            ['Outstanding', 'LKR '.number_format($summary['outstanding'], 2)],
            ['Paid This Month', 'LKR '.number_format($summary['paid_this_month'], 2)],
        ] as [$label, $value])
            <div class="md-card" style="padding:16px">
                <div class="md-body-sm">{{ $label }}</div>
                <div style="font-size:1.45rem;font-weight:800;margin-top:5px">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    @if ($canViewAnalytics && $analytics)
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:16px;margin-bottom:16px">
            <div class="workforce-panel">
                <div class="panel-title">12-Month Billing vs Payments</div>
                <div style="height:290px">
<canvas id="utilityTrendChart">
</canvas>
</div>
            </div>
            <div class="workforce-panel">
                <div class="panel-title">Current-Year Cost by Utility Type</div>
                <div style="height:290px">
<canvas id="utilityTypeChart">
</canvas>
</div>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
            <a class="md-btn md-btn--outlined" href="{{ route('utility-bills.export.csv') }}">Export Utility Report CSV</a>
        </div>
    @endif

    <div class="workforce-panel">
        <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end;margin-bottom:14px">
            <div>
<label class="md-label">Status</label>
<select class="md-input" name="status">
<option value="">All</option>
@foreach(['unpaid'=>'Unpaid','partially_paid'=>'Partially Paid','paid'=>'Paid'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach
</select>
</div>
            <div>
<label class="md-label">Utility Type</label>
<select class="md-input" name="utility_type">
<option value="">All</option>
@foreach(['electricity','water','telephone','internet','sewerage','gas','other'] as $type)<option value="{{ $type }}" @selected(request('utility_type')===$type)>{{ ucfirst($type) }}</option>@endforeach
</select>
</div>
            <div>
<label class="md-label">From</label>
<input class="md-input" type="date" name="from" value="{{ request('from') }}">
</div>
            <div>
<label class="md-label">To</label>
<input class="md-input" type="date" name="to" value="{{ request('to') }}">
</div>
            <div>
<button class="md-btn md-btn--filled">Apply Filters</button>
</div>
        </form>
        <div style="overflow:auto">
            <table class="md-table" style="width:100%">
                <thead>
<tr>
<th>Utility / Account</th>
<th>Period</th>
<th>Due</th>
<th>Bill</th>
<th>Paid</th>
<th>Outstanding</th>
<th>Status</th>
<th>
</th>
</tr>
</thead>
                <tbody>
                @forelse($bills as $bill)
                    @php $overdue = $bill->status !== 'paid' && $bill->due_date->isPast(); @endphp
                    <tr>
                        <td>
<strong>{{ ucfirst($bill->account->utility_type) }}</strong>
<br>
<span class="md-body-sm">{{ $bill->account->provider_name }} · {{ $bill->account->account_no }}</span>
</td>
                        <td>{{ $bill->billing_period_start->format('d M Y') }} – {{ $bill->billing_period_end->format('d M Y') }}</td>
                        <td style="font-weight:{{ $overdue ? '800' : '500' }}">{{ $bill->due_date->format('d M Y') }}{!! $overdue ? '<br>
<span style="color:#b42318">OVERDUE</span>' : '' !!}</td>
                        <td>LKR {{ number_format((float)$bill->bill_amount, 2) }}</td>
                        <td>LKR {{ number_format((float)$bill->amount_paid, 2) }}</td>
                        <td>LKR {{ number_format($bill->outstanding, 2) }}</td>
                        <td>{{ ucwords(str_replace('_',' ', $bill->status)) }}</td>
                        <td>
<a class="md-btn md-btn--text" href="{{ route('utility-bills.show', $bill) }}">Open</a>
</td>
                    </tr>
                @empty
                    <tr>
<td colspan="8" style="text-align:center;padding:28px">No utility bills found.</td>
</tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px">{{ $bills->links() }}</div>
    </div>
@endsection
@if ($canViewAnalytics && $analytics)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($analytics['trend']);
    new Chart(document.getElementById('utilityTrendChart'), {
        type: 'line',
        data: {
            labels: trend.map((item) => item.period),
            datasets: [
                {
                    label: 'Billed (LKR)',
                    data: trend.map((item) => item.billed),
                    tension: 0.3,
                    fill: false,
                },
                {
                    label: 'Paid (LKR)',
                    data: trend.map((item) => item.paid),
                    tension: 0.3,
                    fill: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'bottom',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });

    const byType = @json($analytics['byType']);
    new Chart(document.getElementById('utilityTypeChart'), {
        type: 'bar',
        data: {
            labels: byType.map((item) => item.type),
            datasets: [
                {
                    label: 'Billed (LKR)',
                    data: byType.map((item) => item.total),
                    borderWidth: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });
});
</script>
@endpush
@endif
