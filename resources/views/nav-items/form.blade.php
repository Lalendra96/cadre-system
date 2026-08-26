@extends('layouts.app')
@section('title', $item->exists ? 'Edit Nav Item' : 'New Nav Item')
@section('content')

<div style="max-width:640px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('nav-items.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <h2 class="md-headline-sm">{{ $item->exists ? 'Edit' : 'New' }} Nav Item</h2>
    </div>

    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ $item->exists ? route('nav-items.update', $item) : route('nav-items.store') }}" class="md-card md-card--elevated">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div class="md-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                <div class="md-field">
                    <label class="md-field__label">Section</label>
                    <input type="text" name="section" class="md-field__input @error('section') md-field--error @enderror"
                           value="{{ old('section', $item->section) }}" placeholder="e.g. Reports" maxlength="100">
                    @error('section')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">Sort Order</label>
                    <input type="number" name="sort_order" class="md-field__input" value="{{ old('sort_order', $item->sort_order) }}" min="0" max="9999">
                </div>
            </div>

            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">Label <span style="color:var(--md-error)">*</span></label>
                <input type="text" name="label" autofocus class="md-field__input @error('label') md-field--error @enderror"
                       value="{{ old('label', $item->label) }}" placeholder="e.g. 📊 Unit-wise Breakdown" maxlength="150" required>
                @error('label')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">Route Name <span style="color:var(--md-error)">*</span></label>
                <input type="text" name="route_name" class="md-field__input @error('route_name') md-field--error @enderror"
                       value="{{ old('route_name', $item->route_name) }}" placeholder="e.g. reports.unit-breakdown" maxlength="150" required>
                @error('route_name')<div class="md-field__error">{{ $message }}</div>@enderror
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Must be an existing named route — an invalid name will error when this item tries to render.
                </div>
            </div>

            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:16px;">
                <input type="checkbox" name="open_in_new_tab" value="1" {{ old('open_in_new_tab', $item->open_in_new_tab) ? 'checked' : '' }}>
                <span class="md-label-md">Open in a new tab</span>
            </label>

            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">Restrict to roles</label>
                <div style="display:flex;flex-direction:column;gap:6px;background:var(--md-surface-container);padding:12px;border-radius:var(--md-shape-sm);">
                    @foreach($roleOptions as $role)
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;">
                        <input type="checkbox" name="allowed_roles[]" value="{{ $role }}"
                               {{ in_array($role, old('allowed_roles', $item->allowed_roles ?? [])) ? 'checked' : '' }}>
                        {{ $role }}
                    </label>
                    @endforeach
                </div>
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    Leave everything unticked to allow any logged-in role (still requires login).
                </div>
            </div>

            <div class="md-field">
                <label class="md-field__label">Additional conditions (all must pass)</label>
                <div style="display:flex;flex-direction:column;gap:6px;background:var(--md-surface-container);padding:12px;border-radius:var(--md-shape-sm);">
                    @foreach($checkLabels as $key => $desc)
                    <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:13px;">
                        <input type="checkbox" name="custom_checks[]" value="{{ $key }}" style="margin-top:2px;"
                               {{ in_array($key, old('custom_checks', $item->custom_checks ?? [])) ? 'checked' : '' }}>
                        <span><strong>{{ $key }}</strong><br><span style="color:var(--md-on-surface-variant);font-size:11.5px;">{{ $desc }}</span></span>
                    </label>
                    @endforeach
                </div>
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    These are fixed, pre-defined business-logic checks — you can turn them on/off for this item,
                    but not define new ones here (that requires a code change, for security).
                </div>
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('nav-items.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">{{ $item->exists ? 'Save Changes' : 'Create' }}</button>
        </div>
    </form>
</div>
@endsection
