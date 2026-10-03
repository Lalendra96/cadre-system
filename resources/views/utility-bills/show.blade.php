@extends('layouts.app')
@section('title', 'Utility Bill')
@section('content')
<div style="display:flex;justify-content:space-between;gap:12px;align-items:start;margin-bottom:16px">
<div>
<h2 class="md-headline-sm">{{ ucfirst($bill->account->utility_type) }} Bill</h2>
<p class="md-body-sm">{{ $bill->account->provider_name }} · {{ $bill->account->account_no }} · {{ $bill->account->service_location }}</p>
</div>
<a class="md-btn md-btn--text" href="{{ route('utility-bills.index') }}">← Back</a>
</div>
@include('partials.governance-legal-safeguard', [
    'title' => 'Payment recording safeguard',
    'purpose' => 'Recording a payment confirms an institutional financial event. Verify the supporting payment/voucher evidence and authority before submission.',
    'items' => [
        'Confirm the payment has actually been made and the amount belongs to this bill.',
        'Record a traceable payment/voucher reference and the authority or file reference supporting the action.',
        'Do not enter PINs, passwords, card numbers or online-banking credentials.',
        'If a duplicate or incorrect payment is found, preserve history and use a governed correction rather than deleting evidence.',
    ],
])
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px">
    <div class="workforce-panel">
<div class="panel-title">Bill Details</div>
<table class="md-table" style="width:100%">
<tbody>
        <tr>
<th>Billing Period</th>
<td>{{ $bill->billing_period_start->format('d M Y') }} – {{ $bill->billing_period_end->format('d M Y') }}</td>
</tr>
<tr>
<th>Bill Date</th>
<td>{{ $bill->bill_date->format('d M Y') }}</td>
</tr>
<tr>
<th>Due Date</th>
<td>{{ $bill->due_date->format('d M Y') }}</td>
</tr>
<tr>
<th>Bill Reference</th>
<td>{{ $bill->bill_reference ?: '—' }}</td>
</tr>
<tr>
<th>Total</th>
<td>
<strong>LKR {{ number_format((float)$bill->bill_amount,2) }}</strong>
</td>
</tr>
<tr>
<th>Paid</th>
<td>LKR {{ number_format((float)$bill->amount_paid,2) }}</td>
</tr>
<tr>
<th>Outstanding</th>
<td>
<strong>LKR {{ number_format($bill->outstanding,2) }}</strong>
</td>
</tr>
<tr>
<th>Status</th>
<td>{{ ucwords(str_replace('_',' ', $bill->status)) }}</td>
</tr>
    </tbody>
</table>
</div>
    @if($canManage && $bill->outstanding > 0)
    <form method="POST" action="{{ route('utility-bills.payments.store', $bill) }}" class="workforce-panel">@csrf
<div class="panel-title">Record Payment</div>
<p class="md-body-sm">Record an official payment transaction. The bill balance will update automatically.</p>
        <div class="md-form-row">
<div class="md-form-group">
<label class="md-label">Payment Date *</label>
<input class="md-input" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required>
</div>
<div class="md-form-group">
<label class="md-label">Amount *</label>
<input class="md-input" type="number" step="0.01" min="0.01" max="{{ $bill->outstanding }}" name="amount" value="{{ old('amount', $bill->outstanding) }}" required>
</div>
</div>
        <div class="md-form-row">
<div class="md-form-group">
<label class="md-label">Method *</label>
<select class="md-input" name="payment_method" required>
@foreach(['bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','cash'=>'Cash','online'=>'Online','other'=>'Other'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
</select>
</div>
<div class="md-form-group">
<label class="md-label">Payment Reference *</label>
<input class="md-input" name="payment_reference" value="{{ old('payment_reference') }}" required>
</div>
</div>
        <div class="md-form-group">
<label class="md-label">Voucher No</label>
<input class="md-input" name="voucher_no" value="{{ old('voucher_no') }}">
</div>
<div class="md-form-group">
<label class="md-label">Authority / File Reference *</label>
<input class="md-input" name="authority_reference" maxlength="180" value="{{ old('authority_reference') }}" required>
<small>Financial approval, voucher, file/minute or other traceable institutional reference.</small>
</div>
<div class="md-form-group">
<label class="md-label">Official Administrative Purpose *</label>
<input class="md-input" name="administrative_purpose" maxlength="500" value="{{ old('administrative_purpose', 'Record verified utility payment') }}" required>
</div>
<div class="md-form-group">
<label class="md-label">Remarks</label>
<textarea class="md-input" name="remarks" rows="2">{{ old('remarks') }}</textarea>
</div>
        <div class="alert alert-warning">
<strong>Confirm before recording</strong>
<br>
<label>
<input type="checkbox" name="payment_evidence_reviewed" value="1" required> I reviewed the supporting payment/voucher evidence.</label>
<br>
<label>
<input type="checkbox" name="accuracy_confirmed" value="1" required> I confirmed amount, date, bill and reference accuracy.</label>
<br>
<label>
<input type="checkbox" name="no_duplicate_confirmed" value="1" required> I checked that this payment has not already been recorded.</label>
</div>
<div style="text-align:right">
<button class="md-btn md-btn--filled">Record Verified Payment</button>
</div>
    </form>@endif
</div>
<div class="workforce-panel" style="margin-top:16px">
<div class="panel-title">Payment History</div>
<div style="overflow:auto">
<table class="md-table" style="width:100%">
<thead>
<tr>
<th>Date</th>
<th>Amount</th>
<th>Method</th>
<th>Reference</th>
<th>Voucher</th>
<th>Recorded By</th>
</tr>
</thead>
<tbody>
@forelse($bill->payments->sortByDesc('payment_date') as $p)<tr>
<td>{{ $p->payment_date->format('d M Y') }}</td>
<td>LKR {{ number_format((float)$p->amount,2) }}</td>
<td>{{ ucwords(str_replace('_',' ', $p->payment_method)) }}</td>
<td>{{ $p->payment_reference }}</td>
<td>{{ $p->voucher_no ?: '—' }}</td>
<td>{{ $p->recorder?->name }}</td>
</tr>@empty
<tr>
<td colspan="6" style="text-align:center;padding:24px">No payments recorded.</td>
</tr>@endforelse
</tbody>
</table>
</div>
</div>
@endsection
