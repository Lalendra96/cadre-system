@extends('layouts.app')
@section('title', 'Current Intern Assignments')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Read-only operational view</div>
            <h1 class="page-title">Current Intern Assignments</h1>
            <p class="page-subtitle">Only active assignments are shown. Assignment changes remain restricted to authorised
                Subject Officers and Super Admin.</p>
        </div>
    </div>
    <section class="md-card" style="padding:20px">
        <table class="md-table" style="width:100%">
            <thead>
                <tr>
                    <th>Intern</th>
                    <th>Rotation</th>
                    <th>Appointment</th>
                    <th>Batch</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->intern?->name }}</td>
                        <td>{{ $assignment->rotationUnit?->name }}</td>
                        <td>{{ $assignment->appointment_number }}</td>
                        <td>{{ $assignment->batch?->name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No current assignments.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>{{ $assignments->links() }}
    </section>
@endsection
