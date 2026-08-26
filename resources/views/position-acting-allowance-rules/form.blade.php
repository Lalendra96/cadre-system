@extends('layouts.app')
@section('title', $rule->exists ? 'Edit Acting Allowance Rule' : 'Configure Acting Allowance Rule')
@section('content')

<div style="max-width:600px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('position-acting-allowance-rules.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">{{ $rule->exists ? 'Edit' : 'Configure' }} Acting Allowance Rule</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $position->title }}</p>
        </div>
    </div>

    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST"
          action="{{ $rule->exists ? route('position-acting-allowance-rules.update', $position) : route('position-acting-allowance-rules.store', $position) }}"
          class="md-card md-card--elevated" id="allowanceForm">
        @csrf
        @if($rule->exists) @method('PUT') @endif

        <div class="md-card__body">
            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">Rule Type <span style="color:var(--md-error)">*</span></label>
                <select name="rule_type" id="ruleType" class="md-field__input @error('rule_type') md-field--error @enderror" required>
                    <option value="">— Select —</option>
                    @foreach(\App\Models\PositionActingAllowanceRule::RULE_TYPE_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ old('rule_type', $rule->rule_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('rule_type')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" id="percentageField" style="margin-bottom:16px;">
                <label class="md-field__label">Percentage (%)</label>
                <input type="number" name="percentage" step="0.01" min="0" max="100"
                       class="md-field__input @error('percentage') md-field--error @enderror"
                       value="{{ old('percentage', $rule->percentage) }}">
                @error('percentage')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" id="flatAmountField" style="margin-bottom:16px;">
                <label class="md-field__label">Flat Amount (Rs.)</label>
                <input type="number" name="flat_amount" step="0.01" min="0"
                       class="md-field__input @error('flat_amount') md-field--error @enderror"
                       value="{{ old('flat_amount', $rule->flat_amount) }}">
                @error('flat_amount')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">Notes</label>
                <textarea name="notes" rows="2" maxlength="500" class="md-field__input @error('notes') md-field--error @enderror">{{ old('notes', $rule->notes) }}</textarea>
                @error('notes')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:14px;">
                ℹ Percentage-based rules use the salary scale linked to employees currently in the relevant position(s)
                as the reference figure — configure Salary Scales for those positions first if you haven't already.
            </p>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('position-acting-allowance-rules.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">💾 Save</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var ruleType = document.getElementById('ruleType');
    var pctField = document.getElementById('percentageField');
    var flatField = document.getElementById('flatAmountField');

    function updateVisibility() {
        var isFlat = ruleType.value === 'flat_amount';
        pctField.style.display = isFlat ? 'none' : 'block';
        flatField.style.display = isFlat ? 'block' : 'none';
    }
    ruleType.addEventListener('change', updateVisibility);
    updateVisibility();
})();
</script>
@endpush
