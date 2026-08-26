@extends('layouts.app')
@section('title', $user->exists ? 'Edit User' : 'New User')
@section('content')
<h2 class="md-headline-sm" style="margin-bottom:20px;">{{ $user->exists ? 'Edit User' : 'New User' }}</h2>

<form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="md-card md-card--elevated">
    @csrf
    @if($user->exists) @method('PUT') @endif
    <div class="md-card__body" style="display:flex;flex-direction:column;gap:20px;">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="md-field md-field--outlined">
                <label class="md-field__label">Name <span style="color:var(--md-error)">*</span></label>
                <input type="text" name="name" class="md-field__input @error('name') md-field--error @enderror" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div class="md-field md-field--outlined">
                <label class="md-field__label">Email <span style="color:var(--md-error)">*</span></label>
                <input type="email" name="email" class="md-field__input @error('email') md-field--error @enderror" value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="md-field md-field--outlined">
                <label class="md-field__label">Password{{ $user->exists ? ' (leave blank to keep)' : '' }} {{ $user->exists ? '' : '*' }}</label>
                <input type="password" name="password" class="md-field__input @error('password') md-field--error @enderror" {{ $user->exists ? '' : 'required' }}>
                @error('password')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
            <div class="md-field md-field--outlined">
                <label class="md-field__label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="md-field__input">
            </div>
        </div>

        <div>
            <label class="md-field__label" style="margin-bottom:10px;">Roles <span style="color:var(--md-error)">*</span> <span style="font-size:12px;color:var(--md-on-surface-variant)">(select one or more)</span></label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @php($assignedRoles = old('roles', $user->exists ? $user->userRoles->pluck('role')->all() : []))
                @foreach($allRoles as $r)
                    <label class="md-chip {{ in_array($r, $assignedRoles) ? 'md-chip--selected' : '' }}" style="cursor:pointer;height:auto;padding:8px 14px;">
                        <input type="checkbox" name="roles[]" value="{{ $r }}" {{ in_array($r, $assignedRoles) ? 'checked' : '' }} style="margin-right:6px;">
                        {{ $roleLabels[$r] ?? ucwords(str_replace('_',' ',$r)) }}
                    </label>
                @endforeach
            </div>
            @error('roles')<div class="md-field__error" style="margin-top:4px;">{{ $message }}</div>@enderror
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="md-field md-field--outlined">
                <label class="md-field__label">Category (Admin Group title)</label>
                <select name="category_id" class="md-field__input" style="height:auto;padding:10px 12px;">
                    <option value="">— None —</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ old('category_id', $user->category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="md-field__label" style="margin-bottom:8px;">Subject Codes <span style="font-size:12px;color:var(--md-on-surface-variant)">(required if Subject Officer — hold Ctrl/Cmd for multiple)</span></label>
            <select name="subject_code_ids[]" class="md-field__input @error('subject_code_ids') md-field--error @enderror" multiple size="5" style="height:auto;padding:4px;">
                @php($assigned = old('subject_code_ids', $user->exists ? $user->subjectCodes->pluck('id')->all() : []))
                @foreach($subjectCodes as $sc)
                    <option value="{{ $sc->id }}" {{ in_array($sc->id, $assigned) ? 'selected' : '' }}>{{ $sc->code }} — {{ $sc->name }}</option>
                @endforeach
            </select>
            @error('subject_code_ids')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        <hr class="md-divider">
        <div style="display:flex;flex-direction:column;gap:10px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
                <span class="md-label-lg">Account active</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="can_view_employees" value="1" {{ old('can_view_employees', $user->can_view_employees ?? true) ? 'checked' : '' }}>
                <span class="md-label-lg">Can access Employee Profiles &amp; Monthly Carder Entry</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="can_view_letters" value="1" {{ old('can_view_letters', $user->can_view_letters ?? true) ? 'checked' : '' }}>
                <span class="md-label-lg">Can access Letter Sharing</span>
            </label>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="can_manage_circular_groups" value="1" {{ old('can_manage_circular_groups', $user->can_manage_circular_groups ?? false) ? 'checked' : '' }}>
                <span class="md-label-lg">Can create Position Groups &amp; send Circulars to them</span>
            </label>
        </div>
    </div>
    <div class="md-card__footer">
        <a href="{{ route('users.index') }}" class="md-btn md-btn--text">Cancel</a>
        <button type="submit" class="md-btn md-btn--filled">Save User</button>
    </div>
</form>
@endsection
