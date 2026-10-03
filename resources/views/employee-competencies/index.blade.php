@extends('layouts.app')
@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">Competency assessments</h1>
                <p class="text-muted mb-0">{{ $employee->name }} · record current capability and renewal dates.</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('employees.show', $employee) }}">Back to profile</a>
        </div>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="card mb-4">
            <div class="card-body">
                <form method="post" action="{{ route('employee-competencies.store', $employee) }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Competency</label>
                        <select name="competency_id" class="form-select" required>
                            <option value="">Select…</option>
                            @foreach ($competencies as $competency)
                                <option value="{{ $competency->id }}">{{ $competency->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-select" required>
                            <option>basic</option>
                            <option>intermediate</option>
                            <option>advanced</option>
                            <option>expert</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Assessed on</label>
                        <input type="date" name="assessed_on" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Expires on</label>
                        <input type="date" name="expires_on" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Save assessment</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2">
                        </textarea>
                    </div>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Competency</th>
                        <th>Level</th>
                        <th>Assessed</th>
                        <th>Expires</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $record->competency?->name ?? 'Unknown' }}</td>
                            <td>
                                <span class="badge text-bg-info">{{ ucfirst($record->level) }}</span>
                            </td>
                            <td>{{ optional($record->assessed_on)->format('Y-m-d') }}</td>
                            <td>{{ optional($record->expires_on)->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $record->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">No competency assessments recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
