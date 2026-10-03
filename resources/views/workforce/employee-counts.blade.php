@extends('layouts.app')
@section('title', 'Employee Counts')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Administrative summary</div>
            <h1 class="page-title">Employee Counts by Position</h1>
            <p class="page-subtitle">Aggregated counts only. Individual employee profiles are not available to administrative
                roles.</p>
        </div>
    </div>
    <section class="md-card" style="padding:20px">
        <table class="md-table" style="width:100%">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Active employees</th>
                </tr>
            </thead>
            <tbody>
                @forelse($counts as $position => $count)
                    <tr>
                        <td>{{ $position ?: 'Unassigned position' }}</td>
                        <td>{{ $count }}</td>
                </tr>@empty<tr>
                        <td colspan="2">No active employees.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
