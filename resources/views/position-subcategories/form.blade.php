@extends('layouts.app')
@section('title', $subcategory->exists ? 'Edit Subcategory' : 'New Subcategory')
@section('content')

<div style="max-width:560px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('position-subcategories.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">{{ $subcategory->exists ? 'Edit' : 'New' }} Subcategory</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">Under {{ $position->title }}</p>
        </div>
    </div>

    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST"
          action="{{ $subcategory->exists ? route('position-subcategories.update', [$position, $subcategory]) : route('position-subcategories.store', $position) }}"
          class="md-card md-card--elevated">
        @csrf
        @if($subcategory->exists) @method('PUT') @endif

        <div class="md-card__body">
            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">Name <span style="color:var(--md-error)">*</span></label>
                <input type="text" name="name" autofocus
                       class="md-field__input @error('name') md-field--error @enderror"
                       value="{{ old('name', $subcategory->name) }}" placeholder="e.g. Health Information Consultant" maxlength="150" required>
                @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">Code</label>
                <input type="text" name="code" class="md-field__input @error('code') md-field--error @enderror"
                       value="{{ old('code', $subcategory->code) }}" maxlength="30">
                @error('code')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">Description</label>
                <input type="text" name="description" class="md-field__input @error('description') md-field--error @enderror"
                       value="{{ old('description', $subcategory->description) }}" maxlength="300">
                @error('description')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;background:var(--md-surface-container);padding:14px 16px;border-radius:var(--md-shape-sm);">
                <input type="checkbox" name="counts_toward_parent_total" value="1"
                       {{ old('counts_toward_parent_total', $subcategory->exists ? $subcategory->counts_toward_parent_total : true) ? 'checked' : '' }}
                       style="margin-top:2px;">
                <span>
                    <span class="md-label-md" style="display:block;">Counts toward {{ $position->title }}'s total</span>
                    <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
                        Uncheck for a subcategory that should be tracked and visible on the breakdown, but NOT added to
                        the parent position's main headcount — e.g. PGIM trainees attached to Medical Officer.
                    </span>
                </span>
            </label>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('position-subcategories.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">{{ $subcategory->exists ? 'Save Changes' : 'Create Subcategory' }}</button>
        </div>
    </form>
</div>
@endsection
