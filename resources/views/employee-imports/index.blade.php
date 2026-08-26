@extends('layouts.app')
@section('title', 'Employee Imports')
@section('content')
@php($user = auth()->user())

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Employee Imports</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            @if($user->isSuperAdmin())
                Bulk employee profile imports from CSV/Excel files.
            @else
                Import batches run for your assigned subject codes.
            @endif
        </p>
    </div>
    @if($user->isSuperAdmin())
        <a href="{{ route('employee-imports.create') }}" class="md-btn md-btn--filled">+ New Import</a>
    @endif
</div>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Subject Code</th>
                    <th style="text-align:right;">Rows</th>
                    <th style="text-align:right;">Imported</th>
                    <th style="text-align:right;">Errors</th>
                    <th style="text-align:center;">Status</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $b)
                <tr>
                    <td class="md-label-md">{{ $b->original_filename }}</td>
                    <td><span class="md-badge md-badge--info">{{ $b->subjectCode->code ?? '—' }}</span></td>
                    <td style="text-align:right;">{{ $b->total_rows }}</td>
                    <td style="text-align:right;color:var(--md-success,#2e7d32);">{{ $b->imported_count }}</td>
                    <td style="text-align:right;color:{{ $b->error_count > 0 ? 'var(--md-error)' : 'var(--md-on-surface-variant)' }};">{{ $b->error_count }}</td>
                    <td style="text-align:center;">
                        <span class="md-badge {{ $b->status === 'completed' ? 'md-badge--success' : ($b->status === 'failed' ? 'md-badge--error' : 'md-badge--neutral') }}">
                            {{ ucfirst($b->status) }}
                        </span>
                    </td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $b->uploadedBy->name ?? '—' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $b->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('employee-imports.show', $b) }}" class="md-btn md-btn--icon" title="View Summary">&#128065;</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="md-table__empty">
                        No import batches yet.
                        @if($user->isSuperAdmin())
                            <a href="{{ route('employee-imports.create') }}">Start one →</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $batches->links('vendor.pagination.material') }}</div>
</div>
@endsection
