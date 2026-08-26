@extends('layouts.app')
@section('title', $positionGrade->exists ? 'Edit Grade' : 'New Grade')
@section('content')

<div style="max-width:680px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('position-grades.index', $position) }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">{{ $positionGrade->exists ? 'Edit Grade' : 'New Grade' }}</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $position->title }} ({{ $position->code }})
            </p>
        </div>
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
          action="{{ $positionGrade->exists ? route('position-grades.update', [$position, $positionGrade]) : route('position-grades.store', $position) }}"
          class="md-card md-card--elevated">
        @csrf
        @if($positionGrade->exists) @method('PUT') @endif

        <div class="md-card__body">
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">
                        Grade Name <span style="color:var(--md-error)">*</span>
                    </label>
                    <input type="text" name="name"
                           class="md-field__input @error('name') md-field--error @enderror"
                           value="{{ old('name', $positionGrade->name) }}"
                           placeholder="e.g. Grade II, Senior Grade" maxlength="100" required>
                    @error('name')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>

                <div class="md-field">
                    <label class="md-field__label">
                        Sort Order <span style="color:var(--md-error)">*</span>
                    </label>
                    <input type="number" name="sort_order" min="0" max="9999"
                           class="md-field__input @error('sort_order') md-field--error @enderror"
                           value="{{ old('sort_order', $positionGrade->exists ? $positionGrade->sort_order : $nextOrder) }}" required>
                    @error('sort_order')<div class="md-field__error">{{ $message }}</div>@enderror
                    <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">Lower appears first.</div>
                </div>
            </div>

            <div class="md-field" style="margin-bottom:20px;">
                <label class="md-field__label">Description</label>
                <input type="text" name="description"
                       class="md-field__input @error('description') md-field--error @enderror"
                       value="{{ old('description', $positionGrade->description) }}"
                       placeholder="Optional summary shown in the grading ladder" maxlength="500">
                @error('description')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            {{-- ── Flexible criteria editor ─────────────────────────────────── --}}
            <div style="border-top:1px solid var(--md-outline-variant);padding-top:16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <label class="md-field__label" style="margin:0;">Grading Criteria</label>
                    <button type="button" id="addCriteriaRow" class="md-btn md-btn--outlined" style="font-size:12px;">+ Add Criterion</button>
                </div>
                <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:12px;">
                    Fully flexible — add whatever criteria this grade needs (years of service, qualification, exam requirement, etc.).
                    Use "Yes" / "No" for pass/fail requirements.
                </p>

                <div id="criteriaRows"></div>
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('position-grades.index', $position) }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">
                {{ $positionGrade->exists ? 'Save Changes' : 'Create Grade' }}
            </button>
        </div>
    </form>
</div>

{{-- Row template, cloned by JS — kept outside the form so it's never submitted accidentally --}}
<template id="criteriaRowTemplate">
    <div class="criteria-row" style="display:grid;grid-template-columns:1fr 1fr 40px;gap:10px;margin-bottom:8px;align-items:start;">
        <input type="text" name="criteria_key[]" class="md-field__input" placeholder="e.g. min_years_service" maxlength="60">
        <input type="text" name="criteria_value[]" class="md-field__input" placeholder="e.g. 5, Diploma, Yes" maxlength="200">
        <button type="button" class="md-btn md-btn--icon remove-criteria-row" title="Remove" style="color:var(--md-error);">✕</button>
    </div>
</template>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const container = document.getElementById('criteriaRows');
    const template   = document.getElementById('criteriaRowTemplate');
    const addBtn     = document.getElementById('addCriteriaRow');

    function addRow(key, value) {
        const clone = template.content.cloneNode(true);
        const row   = clone.querySelector('.criteria-row');
        row.querySelector('input[name="criteria_key[]"]').value   = key   ?? '';
        row.querySelector('input[name="criteria_value[]"]').value = value ?? '';
        row.querySelector('.remove-criteria-row').addEventListener('click', function () {
            row.remove();
        });
        container.appendChild(row);
    }

    addBtn.addEventListener('click', function () { addRow(); });

    // Pre-populate existing criteria when editing.
    @if($positionGrade->exists && !empty($positionGrade->criteria))
        @foreach($positionGrade->criteria as $key => $value)
            addRow(
                {!! \Illuminate\Support\Js::from($key) !!},
                {!! \Illuminate\Support\Js::from(is_bool($value) ? ($value ? 'Yes' : 'No') : $value) !!}
            );
        @endforeach
    @else
        addRow(); // start with one empty row for a new grade
    @endif
})();
</script>
@endpush
