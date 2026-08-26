@extends('layouts.app')
@section('title', 'Approved Carder Summary Print')
@section('content')
<div class="no-print md-flex-between" style="margin-bottom:16px;">
    <h1 class="md-h2">Approved Carder Summary — {{ $year }}</h1>
    <button onclick="window.print()" class="md-btn md-btn--primary">Print</button>
</div>

<div class="md-card">
    <div class="md-card__body">
        <h2 class="md-h3 md-text-center">Approved Carder — Summary Report</h2>
        <p class="md-caption md-text-center">Year: {{ $year }} &middot; Generated: {{ now()->format('d M Y, h:i A') }}</p>

        <table class="md-table md-mt-24">
            <thead>
                <tr><th>Position</th><th>Approved Amount</th><th>Ministry Ref.</th></tr>
            </thead>
            <tbody>
                @foreach($carders as $c)
                <tr>
                    <td>{{ $c->position->title ?? '—' }}</td>
                    <td>{{ $c->approved_amount }}</td>
                    <td>{{ $c->ministry_reference_no ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>{{ $totalApproved }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
