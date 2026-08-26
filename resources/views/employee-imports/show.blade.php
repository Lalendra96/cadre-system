@extends('layouts.app')
@section('title', 'Import Summary — ' . $batch->original_filename)
@section('content')

<div style="max-width:900px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('employee-imports.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Import Summary</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $batch->original_filename }} — {{ $batch->subjectCode->code }} ({{ $batch->subjectCode->name }})
            </p>
        </div>
    </div>

    @if(session('success'))
    <div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
                padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        ✓ {{ session('success') }}
    </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
        <div class="md-card md-card--elevated" style="padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:700;color:var(--md-primary);">{{ $batch->total_rows }}</div>
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Total Rows</div>
        </div>
        <div class="md-card md-card--elevated" style="padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:700;color:var(--md-success,#2e7d32);">{{ $batch->imported_count }}</div>
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Imported</div>
        </div>
        <div class="md-card md-card--elevated" style="padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:700;color:var(--md-error);">{{ $batch->error_count }}</div>
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Errors</div>
        </div>
        <div class="md-card md-card--elevated" style="padding:16px;text-align:center;">
            <div style="font-size:26px;font-weight:700;color:var(--md-primary);">{{ $batch->success_rate }}%</div>
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Success Rate</div>
        </div>
    </div>

    <div class="md-card md-card--elevated" style="padding:16px 20px;margin-bottom:20px;">
        <div class="md-title-sm" style="margin-bottom:10px;color:var(--md-primary);">Batch Details</div>
        <div class="md-body-sm" style="display:flex;flex-direction:column;gap:6px;color:var(--md-on-surface-variant);">
            <div><strong style="color:var(--md-on-surface);">Status:</strong>
                <span class="md-badge {{ $batch->status === 'completed' ? 'md-badge--success' : 'md-badge--neutral' }}">
                    {{ ucfirst($batch->status) }}
                </span>
            </div>
            <div><strong style="color:var(--md-on-surface);">Uploaded by:</strong> {{ $batch->uploadedBy->name ?? '—' }}</div>
            <div><strong style="color:var(--md-on-surface);">Completed:</strong> {{ $batch->completed_at?->format('d M Y H:i') ?? '— (not yet run)' }}</div>
        </div>
    </div>

    @if($batch->status === \App\Models\EmployeeImportBatch::STATUS_UPLOADED || $batch->status === \App\Models\EmployeeImportBatch::STATUS_MAPPED)
        @if(auth()->user()->isSuperAdmin())
        <div class="md-card md-card--elevated" style="padding:20px;text-align:center;">
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:12px;">
                This import hasn't been completed yet.
            </p>
            <a href="{{ route('employee-imports.mapping', $batch) }}" class="md-btn md-btn--filled">Continue Import →</a>
        </div>
        @endif
    @endif

    @if(!empty($batch->errors))
    <div class="md-card md-card--elevated">
        <div style="padding:16px 20px;border-bottom:1px solid var(--md-outline-variant);">
            <span class="md-title-sm" style="color:var(--md-error);">Row Errors ({{ $batch->error_count }})</span>
        </div>
        <div style="overflow-x:auto;max-height:400px;overflow-y:auto;">
            <table class="md-table">
                <thead><tr><th style="text-align:center;width:80px;">Row</th><th>Error</th></tr></thead>
                <tbody>
                    @foreach($batch->errors as $err)
                    <tr>
                        <td style="text-align:center;" class="md-body-sm">{{ $err['row'] ?? '—' }}</td>
                        <td class="md-body-sm" style="color:var(--md-error);">{{ $err['message'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@endsection
