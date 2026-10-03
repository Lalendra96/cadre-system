@extends('layouts.app')
@section('title', 'Application Health')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">System administration</div>
            <h1 class="page-title">Application Health</h1>
            <p class="page-subtitle">Check database connectivity, ownership reconciliation and failed background jobs.</p>
        </div>
    </div>
    <section class="md-card" style="padding:20px">
        <table class="md-table" style="width:100%">
            <tbody>
                <tr>
                    <th>Database</th>
                    <td>{{ ucfirst($database) }}</td>
                </tr>
                <tr>
                    <th>Last HR reconciliation</th>
                    <td>{{ $scheduler ?? 'Not completed' }}</td>
                </tr>
                <tr>
                    <th>Failed jobs</th>
                    <td>{{ $failedJobs ?? 'Table not configured' }}</td>
                </tr>
            </tbody>
        </table>
    </section>
@endsection
