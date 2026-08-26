@extends('layouts.app')
@section('title', 'Preview Import — ' . $batch->original_filename)
@section('content')

<div style="max-width:960px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('employee-imports.mapping', $batch) }}" class="md-btn md-btn--icon" title="Back to mapping">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Preview Import</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                Showing the first {{ count($previewRows) }} of {{ $batch->total_rows }} row(s) for {{ $batch->subjectCode->code }} — nothing has been saved yet.
            </p>
        </div>
    </div>

    @if($batch->default_position_id)
    <div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        Every row will import as <strong>{{ $batch->defaultPosition->title ?? '—' }}</strong> (set for this whole file) — no Position column needed.
    </div>
    @endif

    @php($previewValidCount = collect($previewRows)->where('valid', true)->count())
    @php($previewErrorCount = count($previewRows) - $previewValidCount)

    <div style="display:flex;gap:12px;margin-bottom:16px;">
        <div class="md-card md-card--elevated" style="padding:14px 20px;flex:1;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:var(--md-success,#2e7d32);">{{ $previewValidCount }}</div>
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Look valid in preview</div>
        </div>
        <div class="md-card md-card--elevated" style="padding:14px 20px;flex:1;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:var(--md-error);">{{ $previewErrorCount }}</div>
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);">Have errors in preview</div>
        </div>
    </div>

    @if($previewErrorCount > 0)
    <div style="background:color-mix(in srgb,#ff9800 15%,transparent);color:#ff9800;padding:12px 18px;
                border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        ⚠ Some preview rows have errors. You can still proceed — the full import processes every row
        independently, so valid rows will still be created even if others fail. Review the errors below first.
    </div>
    @endif

    <div class="md-card md-card--elevated" style="margin-bottom:20px;">
        <div style="overflow-x:auto;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th style="text-align:center;">Row</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Unit</th>
                        <th style="text-align:center;">Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($previewRows as $r)
                    <tr style="{{ !$r['valid'] ? 'background:color-mix(in srgb,var(--md-error) 6%,transparent);' : '' }}">
                        <td style="text-align:center;" class="md-body-sm">{{ $r['row_number'] }}</td>
                        <td class="md-body-sm">{{ $r['mapped']['name'] ?? '—' }}</td>
                        <td class="md-body-sm">
                            @if($r['resolved']['position_id'] ?? null)
                                {{ \App\Models\Position::find($r['resolved']['position_id'])->title ?? '—' }}
                            @else
                                {{ $r['mapped']['position'] ?? '—' }}
                            @endif
                        </td>
                        <td class="md-body-sm">
                            @if($r['resolved']['unit_id'] ?? null)
                                {{ \App\Models\Unit::find($r['resolved']['unit_id'])->name ?? '—' }}
                            @else
                                <span style="color:var(--md-on-surface-variant);">Unassigned</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($r['valid'])
                                <span class="md-badge md-badge--success">✓ Valid</span>
                            @else
                                <span class="md-badge md-badge--error">✕ Error</span>
                            @endif
                        </td>
                        <td class="md-body-sm" style="color:var(--md-error);max-width:280px;">
                            {{ implode('; ', $r['errors']) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('employee-imports.import', $batch) }}"
          onsubmit="return confirm('Import all {{ $batch->total_rows }} row(s) now? This will create employee profiles for every valid row.');">
        @csrf
        <div style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('employee-imports.mapping', $batch) }}" class="md-btn md-btn--outlined">← Adjust Mapping</a>
            <button type="submit" class="md-btn md-btn--filled">
                ✓ Import All {{ $batch->total_rows }} Row(s)
            </button>
        </div>
    </form>
</div>

@endsection
