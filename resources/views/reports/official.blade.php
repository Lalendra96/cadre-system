@extends('layouts.app')
@section('title', 'Official Reporting')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Governed reports</div>
            <h1 class="page-title">Prepared · Checked · Approved · Signed</h1>
            <p class="page-subtitle">Approved reports create immutable signed workforce snapshots.</p>
        </div>
    </div>
    <section class="md-card" style="padding:20px">
        <table class="md-table" style="width:100%">
            <thead>
                <tr>
                    <th>Report</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                    <tr>
                        <td>{{ $report->report_type }}</td>
                        <td>{{ $report->period_key }}</td>
                        <td>{{ ucfirst($report->status) }}</td>
                        <td>
                            @if ($report->status === 'prepared')
                                <form method="POST" action="{{ route('official-reports.advance', $report) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="checked">
                                    <button class="md-btn--primary">Mark checked</button>
                                </form>
                            @elseif($report->status === 'checked')
                                <form method="POST" action="{{ route('official-reports.advance', $report) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">
                                    <button class="md-btn--primary">Approve</button>
                                </form>
                            @elseif($report->status === 'approved')
                                <form method="POST" action="{{ route('official-reports.sign', $report) }}">
                                    @csrf
                                    <button class="md-btn--primary">Sign & archive snapshot</button>
                                </form>
                            @else
                                Immutable snapshot archived
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No official reports.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>{{ $reports->links() }}
    </section>
@endsection
