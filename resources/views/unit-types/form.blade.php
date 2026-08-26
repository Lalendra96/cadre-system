@extends('layouts.app')
@section('title', $unitType->exists ? 'Edit Unit Type' : 'New Unit Type')
@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
    <a href="{{ route('unit-types.index') }}" class="md-btn md-btn--icon">&#8592;</a>
    <h2 class="md-headline-sm">{{ $unitType->exists ? 'Edit Unit Type' : 'Add Unit Type' }}</h2>
</div>

@if($errors->any())
<div style="background:var(--md-error-container);color:var(--md-on-error-container);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    <ul style="margin:0;padding-left:16px;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST"
      action="{{ $unitType->exists ? route('unit-types.update', $unitType) : route('unit-types.store') }}"
      class="md-card md-card--elevated">
    @csrf
    @if($unitType->exists) @method('PUT') @endif

    <div class="md-card__body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

        <div class="md-field">
            <label class="md-field__label">
                Type Name <span style="color:var(--md-error)">*</span>
            </label>
            <input type="text" name="name"
                   class="md-field__input @error('name') md-field--error @enderror"
                   value="{{ old('name', $unitType->name) }}"
                   placeholder="e.g. Ward, Department, OPD Clinic"
                   maxlength="80" required>
            @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        <div class="md-field">
            <label class="md-field__label">Short Code</label>
            <input type="text" name="code"
                   class="md-field__input @error('code') md-field--error @enderror"
                   value="{{ old('code', $unitType->code) }}"
                   placeholder="e.g. WARD, OPD, LAB"
                   maxlength="20">
            @error('code')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        <div class="md-field" style="grid-column:1/-1;">
            <label class="md-field__label">Description</label>
            <textarea name="description"
                      class="md-field__input @error('description') md-field--error @enderror"
                      rows="2" maxlength="500"
                      placeholder="Brief description of this unit type…">{{ old('description', $unitType->description) }}</textarea>
            @error('description')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        <div class="md-field">
            <label class="md-field__label">Sort Order</label>
            <input type="number" name="sort_order"
                   class="md-field__input"
                   value="{{ old('sort_order', $unitType->sort_order ?? $nextOrder ?? 10) }}"
                   min="0" max="9999">
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                Lower numbers appear first in all dropdowns.
            </div>
        </div>

        <div class="md-field" style="display:flex;align-items:center;gap:12px;margin-top:20px;">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $unitType->is_active ?? true) ? 'checked' : '' }}
                   style="width:18px;height:18px;cursor:pointer;">
            <label for="is_active" class="md-label-md" style="cursor:pointer;">Active</label>
        </div>

    </div>

    @if($unitType->exists && isset($unitType->units_count) && $unitType->units_count > 0)
    <div style="background:var(--md-surface-container);padding:12px 20px;
                border-top:1px solid var(--md-outline-variant);font-size:12px;
                color:var(--md-on-surface-variant);">
        ℹ {{ $unitType->units_count }} unit(s) are currently using this type.
        Renaming it will update all associated units automatically.
    </div>
    @endif

    <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 20px;">
        <a href="{{ route('unit-types.index') }}" class="md-btn md-btn--outlined">Cancel</a>
        <button type="submit" class="md-btn md-btn--filled">
            {{ $unitType->exists ? 'Save Changes' : 'Create Unit Type' }}
        </button>
    </div>
</form>
@endsection
