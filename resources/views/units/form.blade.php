@extends('layouts.app')
@section('title', $unit->exists ? 'Edit Unit' : 'New Unit')
@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
    <a href="{{ route('units.index') }}" class="md-btn md-btn--icon">&#8592;</a>
    <h2 class="md-headline-sm">{{ $unit->exists ? 'Edit Unit' : 'Add New Unit' }}</h2>
</div>

@if($errors->any())
<div style="background:var(--md-error-container);color:var(--md-on-error-container);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    <div style="font-weight:600;margin-bottom:4px;">Please fix the following:</div>
    <ul style="margin:0;padding-left:16px;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST"
      action="{{ $unit->exists ? route('units.update', $unit) : route('units.store') }}"
      class="md-card md-card--elevated">
    @csrf
    @if($unit->exists) @method('PUT') @endif

    <div class="md-card__body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

        {{-- Code --}}
        <div class="md-field">
            <label class="md-field__label">
                Unit Code <span style="color:var(--md-error)">*</span>
            </label>
            <input type="text" name="code"
                   class="md-field__input @error('code') md-field--error @enderror"
                   value="{{ old('code', $unit->code) }}"
                   placeholder="e.g. WARD-01, OPD-A"
                   maxlength="30" required>
            @error('code')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        {{-- Name --}}
        <div class="md-field">
            <label class="md-field__label">
                Unit Name <span style="color:var(--md-error)">*</span>
            </label>
            <input type="text" name="name"
                   class="md-field__input @error('name') md-field--error @enderror"
                   value="{{ old('name', $unit->name) }}"
                   placeholder="e.g. Ward 1 – General Medicine"
                   maxlength="120" required>
            @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        {{-- Unit Type — loaded dynamically from unit_types table --}}
        <div class="md-field">
            <label class="md-field__label">
                Unit Type <span style="color:var(--md-error)">*</span>
            </label>
            <select name="unit_type_id"
                    class="md-field__input @error('unit_type_id') md-field--error @enderror"
                    required>
                <option value="">— Select type —</option>
                @foreach($unitTypes as $type)
                    <option value="{{ $type->id }}"
                        {{ old('unit_type_id', $unit->unit_type_id) == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
            @error('unit_type_id')<div class="md-field__error">{{ $message }}</div>@enderror
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                Unit types are managed in
                <a href="{{ route('unit-types.index') }}" style="color:var(--md-primary);">
                    Admin → Unit Types
                </a>.
            </div>
        </div>

        {{-- Location --}}
        <div class="md-field">
            <label class="md-field__label">Location / Block</label>
            <input type="text" name="location"
                   class="md-field__input @error('location') md-field--error @enderror"
                   value="{{ old('location', $unit->location) }}"
                   placeholder="e.g. Block A, Ground Floor"
                   maxlength="150">
            @error('location')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        {{-- Active toggle --}}
        <div class="md-field" style="grid-column:1/-1;display:flex;align-items:center;gap:12px;">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $unit->is_active ?? true) ? 'checked' : '' }}
                   style="width:18px;height:18px;cursor:pointer;">
            <label for="is_active" class="md-label-md" style="cursor:pointer;">
                Active — unit appears in lists and allocation forms
            </label>
        </div>

    </div>

    <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 20px;">
        <a href="{{ route('units.index') }}" class="md-btn md-btn--outlined">Cancel</a>
        <button type="submit" class="md-btn md-btn--filled">
            {{ $unit->exists ? 'Save Changes' : 'Create Unit' }}
        </button>
    </div>
</form>
@endsection
