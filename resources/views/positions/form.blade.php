@extends('layouts.app')
@section('title', $position->exists ? 'Edit Position' : 'New Position')
@section('content')
<h1 class="md-h2" style="margin-bottom:16px;">{{ $position->exists ? 'Edit Position' : 'New Position' }}</h1>
<form method="POST" action="{{ $position->exists ? route('positions.update', $position) : route('positions.store') }}" class="md-card">
    @csrf
    @if($position->exists) @method('PUT') @endif
    <div class="md-card__body">
        <div class="md-form-row">
            <div class="md-form-group">
                <label class="md-label md-label--required">Code</label>
                <input type="text" name="code" class="md-input @error('code') md-input--error @enderror" value="{{ old('code', $position->code) }}" required>
                @error('code')<div class="md-field-error">{{ $message }}</div>@enderror
            </div>
            <div class="md-form-group">
                <label class="md-label md-label--required">Title</label>
                <input type="text" name="title" class="md-input @error('title') md-input--error @enderror" value="{{ old('title', $position->title) }}" placeholder="e.g. Staff Nurse, Nursing Officer" required>
                @error('title')<div class="md-field-error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="md-form-group md-check-group">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $position->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="md-caption">Active</label>
        </div>
    </div>
    <div class="md-card__footer">
        <a href="{{ route('positions.index') }}" class="md-btn md-btn--ghost">Cancel</a>
        <button type="submit" class="md-btn md-btn--primary">Save</button>
    </div>
</form>
@endsection
