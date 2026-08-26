@extends('layouts.app')
@section('title', 'Map Columns — ' . $batch->original_filename)
@section('content')

<div style="max-width:820px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('employee-imports.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Map Columns</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $batch->original_filename }} — {{ $batch->total_rows }} row(s) for {{ $batch->subjectCode->code }}
            </p>
        </div>
    </div>

    @if(session('error'))
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        {{ session('error') }}
    </div>
    @endif

    <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:16px;">
        For each column found in your file, choose which employee field it should map to — or leave it
        as "— Skip this column —" to ignore it. <strong>Full Name is required.</strong>
    </p>

    <form method="POST" action="{{ route('employee-imports.mapping.store', $batch) }}" class="md-card md-card--elevated">
        @csrf

        <div class="md-card__body" style="border-bottom:1px solid var(--md-outline-variant);">
            <div class="md-field">
                <label class="md-field__label">Default Position for this file (optional)</label>
                <select name="default_position_id" class="md-field__input" style="max-width:400px;">
                    <option value="">— None, I'll map a Position column below —</option>
                    @foreach(\App\Models\Position::active()->orderBy('title')->get() as $pos)
                        <option value="{{ $pos->id }}" {{ old('default_position_id', $batch->default_position_id) == $pos->id ? 'selected' : '' }}>
                            {{ $pos->title }}
                        </option>
                    @endforeach
                </select>
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Set this if every row in the file is the same position (e.g. a file that's already
                    split per position) — every employee imports with this position and you don't need
                    to map a Position column at all. If your file has mixed positions, leave this blank
                    and map a Position column below instead.
                </div>
            </div>
        </div>

        <div class="md-card__body">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Column in File</th>
                        <th>Maps to Employee Field</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batch->column_headers as $index => $header)
                    <tr>
                        <td class="md-label-md">
                            {{ $header !== '' ? $header : '(column ' . ($index + 1) . ')' }}
                        </td>
                        <td>
                            <select name="mapping[{{ $index }}]" class="md-field__input" style="max-width:320px;">
                                <option value="">— Skip this column —</option>
                                @foreach($fields as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old("mapping.{$index}", $batch->column_mapping[$index] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('employee-imports.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">Save Mapping &amp; Preview →</button>
        </div>
    </form>
</div>

@endsection
