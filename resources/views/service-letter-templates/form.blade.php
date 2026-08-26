@extends('layouts.app')
@section('title', $template->exists ? 'Edit Template' : 'New Service Letter Template')
@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
    <a href="{{ route('service-letter-templates.index') }}" class="md-btn md-btn--icon">&#8592;</a>
    <h2 class="md-headline-sm">{{ $template->exists ? 'Edit Template' : 'New Service Letter Template' }}</h2>
</div>

@if($errors->any())
<div style="background:var(--md-error-container);color:var(--md-on-error-container);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    <ul style="margin:0;padding-left:16px;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start;">

    <form method="POST"
          action="{{ $template->exists ? route('service-letter-templates.update', $template) : route('service-letter-templates.store') }}"
          class="md-card md-card--elevated">
        @csrf
        @if($template->exists) @method('PUT') @endif

        <div class="md-card__body">
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">
                        Template Name <span style="color:var(--md-error)">*</span>
                    </label>
                    <input type="text" name="name"
                           class="md-field__input @error('name') md-field--error @enderror"
                           value="{{ old('name', $template->name) }}"
                           placeholder="e.g. Service Confirmation Letter"
                           maxlength="150" required>
                    @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>

                <div class="md-field">
                    <label class="md-field__label">
                        Language <span style="color:var(--md-error)">*</span>
                    </label>
                    <select name="language" class="md-field__input @error('language') md-field--error @enderror" required>
                        <option value="">—</option>
                        @foreach(\App\Models\ServiceLetterTemplate::LANGUAGES as $code => $label)
                            <option value="{{ $code }}" {{ old('language', $template->language) === $code ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('language')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Description</label>
                <input type="text" name="description"
                       class="md-field__input"
                       value="{{ old('description', $template->description) }}"
                       placeholder="Shown to officers when picking a template"
                       maxlength="300">
            </div>

            <div class="md-field" style="margin-bottom:8px;">
                <label class="md-field__label">
                    Body <span style="color:var(--md-error)">*</span>
                </label>
                <textarea name="body" rows="14"
                          class="md-field__input @error('body') md-field--error @enderror"
                          style="font-family:monospace;font-size:13px;line-height:1.6;"
                          placeholder="This is to certify that @{{employee_name}} (NIC: @{{nic_number}}) holding the post of @{{position_title}}..."
                          required>{{ old('body', $template->body) }}</textarea>
                @error('body')<div class="md-field__error">{{ $message }}</div>@enderror
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Insert placeholders from the reference panel — they'll be replaced with the employee's actual details when a letter is drafted.
                </div>
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('service-letter-templates.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">
                {{ $template->exists ? 'Save Changes' : 'Create Template' }}
            </button>
        </div>
    </form>

    {{-- Placeholder reference panel --}}
    <div class="md-card md-card--elevated" style="padding:18px;position:sticky;top:80px;">
        <div class="md-title-sm" style="margin-bottom:10px;color:var(--md-primary);">Available Placeholders</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($placeholders as $token => $desc)
                <div style="font-size:12px;">
                    <code style="background:var(--md-surface-container);padding:2px 6px;border-radius:4px;
                                  color:var(--md-primary);font-weight:600;">{{ $token }}</code>
                    <div style="color:var(--md-on-surface-variant);margin-top:2px;">{{ $desc }}</div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
