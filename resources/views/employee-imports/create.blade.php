@extends('layouts.app')
@section('title', 'Import Employees')
@section('content')

<div style="max-width:640px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('employee-imports.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Import Employees</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                Upload a CSV or Excel file, map its columns to employee fields, then review before importing.
            </p>
        </div>
    </div>

    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif
    @if(session('error'))
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        {{ session('error') }}
    </div>
    @endif

    <form method="POST" action="{{ route('employee-imports.store') }}" enctype="multipart/form-data" class="md-card md-card--elevated">
        @csrf

        <div class="md-card__body">
            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">
                    Subject Code <span style="color:var(--md-error)">*</span>
                </label>
                <select name="subject_code_id" class="md-field__input @error('subject_code_id') md-field--error @enderror" required>
                    <option value="">— Select subject code —</option>
                    @foreach($subjectCodes as $sc)
                        <option value="{{ $sc->id }}" {{ old('subject_code_id') == $sc->id ? 'selected' : '' }}>
                            {{ $sc->code }} — {{ $sc->name }}
                        </option>
                    @endforeach
                </select>
                @error('subject_code_id')<div class="md-field__error">{{ $message }}</div>@enderror
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Every employee in this file will be created under this one subject code.
                    For multiple codes, run a separate import per code.
                </div>
            </div>

            <div class="md-field">
                <label class="md-field__label">
                    File <span style="color:var(--md-error)">*</span>
                </label>
                <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" required
                       class="md-field__input @error('file') md-field--error @enderror">
                @error('file')<div class="md-field__error">{{ $message }}</div>@enderror
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    CSV or Excel (.xlsx), max 5MB. The first row must contain column headers.
                </div>
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('employee-imports.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">Upload &amp; Continue →</button>
        </div>
    </form>
</div>

@endsection
