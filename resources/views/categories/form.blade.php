@extends('layouts.app')
@section('title', $category->exists ? 'Edit Category' : 'New Category')

@section('content')
<h2 class="md-headline-sm" style="margin-bottom:16px;">
    {{ $category->exists ? 'Edit Category' : 'New Category' }}
</h2>

<form method="POST"
      action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}"
      class="md-card md-card--elevated" style="max-width:600px;">
    @csrf
    @if($category->exists) @method('PUT') @endif

    <div class="md-card__body" style="display:flex;flex-direction:column;gap:18px;">

        @if($errors->any())
            <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                        padding:12px 16px;border-radius:var(--md-shape-sm);font-size:13px;">
                @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
        @endif

        <div class="md-field">
            <label class="md-field__label">Category Name <span style="color:var(--md-error)">*</span></label>
            <input type="text" name="name"
                   class="md-field__input @error('name') md-field--error @enderror"
                   value="{{ old('name', $category->name) }}"
                   placeholder="e.g. Deputy Director General"
                   required>
            @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        <div class="md-field">
            <label class="md-field__label">Description <span class="md-body-sm" style="color:var(--md-on-surface-variant);font-weight:400;">(optional)</span></label>
            <input type="text" name="description"
                   class="md-field__input"
                   value="{{ old('description', $category->description) }}"
                   placeholder="Brief note about this role">
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                For roles like Deputy Director where there are multiple officers (I, II, III … N):
                create <strong>one</strong> category entry and assign each officer to it individually.
                They will all appear as separate recipients in Letter Sharing.
            </div>
        </div>

        <div class="md-field">
            <label class="md-field__label">Display Order</label>
            <input type="number" name="sort_order"
                   class="md-field__input @error('sort_order') md-field--error @enderror"
                   value="{{ old('sort_order', $category->sort_order ?? ($nextOrder ?? 0)) }}"
                   min="0" max="9999" style="max-width:140px;">
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                Lower numbers appear first in pickers and the index. Use increments of 10 to leave room.
            </div>
            @error('sort_order')<div class="md-field__error">{{ $message }}</div>@enderror
        </div>

        <hr class="md-divider">

        {{-- ── Letter Sharing permission ──────────────────────────────────── --}}
        <div style="background:var(--md-surface-container);border-radius:var(--md-shape-sm);padding:16px;">
            <div class="md-title-sm" style="margin-bottom:10px;">Letter Sharing Permission</div>
            <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;">
                <input type="checkbox" name="can_receive_letters" value="1"
                       {{ old('can_receive_letters', $category->can_receive_letters ?? false) ? 'checked' : '' }}
                       style="margin-top:2px;flex-shrink:0;">
                <div>
                    <div class="md-label-lg">Allow as Letter Recipient</div>
                    <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:2px;">
                        When checked, Admin Group users assigned this category will appear in the
                        Letter Sharing recipient picker. Changes take effect immediately — no
                        code deployment required.
                    </div>
                </div>
            </label>
        </div>

        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:4px 0;">
            <input type="checkbox" name="is_active" value="1"
                   {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
            <span class="md-label-lg">Active</span>
        </label>
    </div>

    <div class="md-card__footer">
        <a href="{{ route('categories.index') }}" class="md-btn md-btn--text">Cancel</a>
        <button type="submit" class="md-btn md-btn--filled">Save Category</button>
    </div>
</form>

@if($category->exists)
    <div style="margin-top:16px;max-width:600px;">
        <div class="md-card md-card--outlined" style="padding:16px 20px;">
            <div class="md-title-sm" style="margin-bottom:10px;">Users in this category</div>
            @forelse($category->users()->active()->orderBy('name')->get() as $u)
                <div style="display:flex;align-items:center;gap:10px;padding:5px 0;">
                    <span style="flex:1;font-size:13px;">{{ $u->name }}</span>
                    <span class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $u->email }}</span>
                    <a href="{{ route('users.edit', $u) }}" class="md-btn md-btn--icon" style="font-size:13px;">&#9998;</a>
                </div>
            @empty
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);">No active users assigned to this category.</p>
            @endforelse
        </div>
    </div>
@endif
@endsection
