@extends('layouts.app')
@section('title', 'Training and Competencies')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Employee development</div>
            <h1 class="page-title">Training and Competencies</h1>
            <p class="page-subtitle">Record completed training and certificate expiry for {{ $employee->display_name }}.</p>
        </div>
        <a class="md-btn md-btn--outlined" href="{{ route('employees.show', $employee) }}">Back to profile</a>
    </div>
    <section class="md-card" style="padding:20px;margin-bottom:16px">
        <form method="POST" action="{{ route('employee-training.store', $employee) }}"
            style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px">
            @csrf
            <input class="md-input" name="course_name" placeholder="Course or competency training" required>
            <input class="md-input" name="provider" placeholder="Provider / institution">
            <input class="md-input" type="date" name="completed_on">
            <input class="md-input" type="date" name="expires_on">
            <input class="md-input" name="certificate_reference" placeholder="Certificate reference">
            <button class="md-btn--primary" style="grid-column:1/-1">Add training record</button>
        </form>
    </section>
    <section class="md-card" style="padding:20px">
        <table class="md-table" style="width:100%">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Provider</th>
                    <th>Completed</th>
                    <th>Expires</th>
                    <th>Certificate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($training as $record)
                    <tr>
                        <td>{{ $record->course_name }}</td>
                        <td>{{ $record->provider ?? '—' }}</td>
                        <td>{{ $record->completed_on?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $record->expires_on?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $record->certificate_reference ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No training records.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
