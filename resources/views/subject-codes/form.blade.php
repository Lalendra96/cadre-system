@extends('layouts.app')
@section('title', $subjectCode->exists ? 'Edit Subject Code' : 'New Subject Code')
@section('content')
<h2 class="md-headline-sm" style="margin-bottom:16px;">{{ $subjectCode->exists ? 'Edit Subject Code' : 'New Subject Code' }}</h2>

<form method="POST"
      action="{{ $subjectCode->exists ? route('subject-codes.update', $subjectCode) : route('subject-codes.store') }}"
      class="md-card md-card--elevated">
    @csrf
    @if($subjectCode->exists) @method('PUT') @endif

    <div class="md-card__body" style="display:flex;flex-direction:column;gap:18px;">

        <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;">
            <div class="md-field">
                <label class="md-field__label">Code <span style="color:var(--md-error)">*</span></label>
                <input type="text" name="code"
                       class="md-field__input @error('code') md-field--error @enderror"
                       value="{{ old('code', $subjectCode->code) }}" required>
                @error('code')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div class="md-field">
                <label class="md-field__label">Name <span style="color:var(--md-error)">*</span></label>
                <input type="text" name="name"
                       class="md-field__input @error('name') md-field--error @enderror"
                       value="{{ old('name', $subjectCode->name) }}" required>
                @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="md-field">
            <label class="md-field__label">
                Positions
                <span class="md-body-sm" style="color:var(--md-on-surface-variant);font-weight:400;">
                    — one subject code can cover multiple positions
                </span>
            </label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;padding:12px;background:var(--md-surface-container-high);border:1px solid var(--md-outline);border-radius:var(--md-shape-xs);">
                @php($selected = old('position_ids', $subjectCode->exists ? $subjectCode->positions->pluck('id')->all() : []))
                @forelse($positions as $p)
                    <label class="md-chip {{ in_array($p->id, $selected) ? 'md-chip--selected' : '' }}"
                           style="cursor:pointer;height:auto;padding:7px 14px;">
                        <input type="checkbox" name="position_ids[]" value="{{ $p->id }}"
                               {{ in_array($p->id, $selected) ? 'checked' : '' }}
                               style="margin-right:6px;">
                        {{ $p->title }}
                    </label>
                @empty
                    <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                        No positions defined yet — add them under Carder → Positions first.
                    </p>
                @endforelse
            </div>
            @error('position_ids')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
            <input type="checkbox" name="is_active" value="1"
                   {{ old('is_active', $subjectCode->is_active ?? true) ? 'checked' : '' }}>
            <span class="md-label-lg">Active</span>
        </label>
    </div>

    <div class="md-card__footer">
        <a href="{{ route('subject-codes.index') }}" class="md-btn md-btn--text">Cancel</a>
        <button type="submit" class="md-btn md-btn--filled">Save Subject Code</button>
    </div>
</form>
@endsection
