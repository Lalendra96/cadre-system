@extends('layouts.app')

@section('title', 'Add Knowledge Source')

@section('content')
    <div class="md-page-header">
        <div>
            <div class="md-label-md" style="color: var(--md-primary);">CONTROLLED KB</div>
            <h1 class="page-title">Add Local Knowledge Source</h1>
            <p class="page-subtitle">Register source text and provenance. The record remains unverified until a separate verification action is completed.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('offline-assistant.sources.store') }}" class="md-card">
        @csrf
        <div class="md-card__content">
            <div class="md-form-row">
                <div class="md-form-group">
                    <label class="md-label md-label--required" for="title">Source title</label>
                    <input id="title" name="title" class="md-input" value="{{ old('title') }}" required maxlength="220">
                </div>
                <div class="md-form-group">
                    <label class="md-label md-label--required" for="source_type">Source type</label>
                    <select id="source_type" name="source_type" class="md-select" required>
                        @foreach (['application_help', 'user_manual', 'policy', 'circular', 'governance', 'planning_guideline', 'procedure'] as $type)
                            <option value="{{ $type }}" @selected(old('source_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md-form-group">
                    <label class="md-label md-label--required" for="language">Language</label>
                    <select id="language" name="language" class="md-select" required>
                        <option value="en">English</option>
                        <option value="si">Sinhala</option>
                        <option value="ta">Tamil</option>
                    </select>
                </div>
            </div>

            <div class="md-form-row">
                <div class="md-form-group">
                    <label class="md-label" for="reference_no">Official reference number</label>
                    <input id="reference_no" name="reference_no" class="md-input" value="{{ old('reference_no') }}" maxlength="120">
                </div>
                <div class="md-form-group">
                    <label class="md-label" for="issuing_authority">Issuing authority</label>
                    <input id="issuing_authority" name="issuing_authority" class="md-input" value="{{ old('issuing_authority') }}" maxlength="180">
                </div>
                <div class="md-form-group">
                    <label class="md-label md-label--required" for="classification">Classification</label>
                    <select id="classification" name="classification" class="md-select" required>
                        <option value="internal">Internal</option>
                        <option value="public">Public</option>
                        <option value="confidential">Confidential — excluded from general assistant retrieval</option>
                    </select>
                </div>
            </div>

            <div class="md-form-row">
                <div class="md-form-group">
                    <label class="md-label" for="effective_date">Effective date</label>
                    <input type="date" id="effective_date" name="effective_date" class="md-input" value="{{ old('effective_date') }}">
                </div>
                <div class="md-form-group">
                    <label class="md-label" for="expiry_date">Expiry / review date</label>
                    <input type="date" id="expiry_date" name="expiry_date" class="md-input" value="{{ old('expiry_date') }}">
                </div>
                <div class="md-form-group">
                    <label class="md-label" for="source_location">Source location / file reference</label>
                    <input id="source_location" name="source_location" class="md-input" value="{{ old('source_location') }}" maxlength="255">
                </div>
            </div>

            <div class="md-form-group">
                <label class="md-label md-label--required" for="content">Approved source text</label>
                <textarea id="content" name="content" class="md-input" rows="14" required>{{ old('content') }}</textarea>
                <span class="md-caption">Paste only text you are authorised to register. Do not add personal case data merely to make the assistant more specific.</span>
            </div>
        </div>
        <div class="md-card__actions" style="display:flex;gap:8px;justify-content:flex-end;">
            <a class="md-btn md-btn--text" href="{{ route('offline-assistant.sources.index') }}">Cancel</a>
            <button class="md-btn md-btn--filled" type="submit">Save as unverified</button>
        </div>
    </form>
@endsection
