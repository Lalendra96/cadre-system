@extends('layouts.app')
@section('title', $group->exists ? 'Edit Position Group' : 'New Position Group')
@section('content')

<div style="max-width:640px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('position-groups.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <h2 class="md-headline-sm">{{ $group->exists ? 'Edit Position Group' : 'New Position Group' }}</h2>
    </div>

    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST"
          action="{{ $group->exists ? route('position-groups.update', $group) : route('position-groups.store') }}"
          class="md-card md-card--elevated">
        @csrf
        @if($group->exists) @method('PUT') @endif

        <div class="md-card__body">
            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">
                    Group Name <span style="color:var(--md-error)">*</span>
                </label>
                <input type="text" name="name" autofocus
                       class="md-field__input @error('name') md-field--error @enderror"
                       value="{{ old('name', $group->name) }}" placeholder="e.g. Nursing Staff" maxlength="150" required>
                @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" style="margin-bottom:20px;">
                <label class="md-field__label">Description</label>
                <input type="text" name="description"
                       class="md-field__input @error('description') md-field--error @enderror"
                       value="{{ old('description', $group->description) }}" maxlength="500">
                @error('description')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">
                    Positions in this group <span style="color:var(--md-error)">*</span>
                </label>
                @error('position_ids')<div class="md-field__error" style="margin-bottom:8px;">{{ $message }}</div>@enderror
                <div style="max-height:280px;overflow-y:auto;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);padding:12px;">
                    @foreach($positions as $p)
                    <label style="display:flex;align-items:center;gap:8px;padding:6px 4px;cursor:pointer;font-size:13px;">
                        <input type="checkbox" name="position_ids[]" value="{{ $p->id }}"
                               {{ in_array($p->id, old('position_ids', $selectedPositionIds)) ? 'checked' : '' }}>
                        {{ $p->title }}
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('position-groups.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">
                {{ $group->exists ? 'Save Changes' : 'Create Group' }}
            </button>
        </div>
    </form>
</div>
@endsection
