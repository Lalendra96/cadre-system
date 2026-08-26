@extends('layouts.app')
@section('title', 'Upload Circular')
@section('content')

<div style="max-width:560px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('circulars.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Upload Circular</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                Once uploaded, this document gets a public link anyone can use to view it — no login required.
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

    <form method="POST" action="{{ route('circulars.store') }}" enctype="multipart/form-data" class="md-card md-card--elevated">
        @csrf

        <div class="md-card__body">
            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">
                    Title <span style="color:var(--md-error)">*</span>
                </label>
                <input type="text" name="title" autofocus
                       class="md-field__input @error('title') md-field--error @enderror"
                       value="{{ old('title') }}" maxlength="200" required>
                @error('title')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">
                    Category <span style="color:var(--md-error)">*</span>
                </label>
                <select name="category" class="md-field__input @error('category') md-field--error @enderror" required>
                    <option value="">— Select —</option>
                    @foreach(\App\Models\Circular::CATEGORY_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">Description</label>
                <textarea name="description" rows="3" maxlength="1000"
                          class="md-field__input @error('description') md-field--error @enderror"
                          placeholder="Optional summary shown on the public page">{{ old('description') }}</textarea>
                @error('description')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">
                    File <span style="color:var(--md-error)">*</span>
                </label>
                <input type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required
                       class="md-field__input @error('file') md-field--error @enderror">
                @error('file')<div class="md-field__error">{{ $message }}</div>@enderror
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    PDF, Word document, or image. Max 10MB.
                </div>
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('circulars.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">Upload &amp; Get Link</button>
        </div>
    </form>
</div>

@endsection
