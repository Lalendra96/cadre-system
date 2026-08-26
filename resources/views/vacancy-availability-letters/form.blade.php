@extends('layouts.app')
@section('title', 'Issue Vacancy Availability Letter')
@section('content')

<div style="max-width:640px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('vacancy-availability-letters.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Issue Vacancy Availability Letter</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                A formal, e-signed declaration valid for 90 days. Subject Officers under the selected
                subject code will be notified immediately.
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

    @if(! auth()->user()->eSignature)
        <div class="md-card md-card--elevated" style="padding:32px;text-align:center;color:var(--md-on-surface-variant);">
            <div style="font-size:28px;margin-bottom:8px;">✍️</div>
            <p class="md-body-sm" style="margin-bottom:14px;">
                You need to register an e-signature before you can issue a vacancy availability letter.
            </p>
            <a href="{{ route('e-signatures.edit') }}" class="md-btn md-btn--filled">Register E-Signature</a>
        </div>
    @else

    <form method="POST" action="{{ route('vacancy-availability-letters.store') }}" class="md-card md-card--elevated">
        @csrf

        <div class="md-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">
                        Position <span style="color:var(--md-error)">*</span>
                    </label>
                    <select name="position_id" class="md-field__input @error('position_id') md-field--error @enderror" required>
                        <option value="">— Select position —</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->id }}" {{ old('position_id') == $p->id ? 'selected' : '' }}>{{ $p->title }}</option>
                        @endforeach
                    </select>
                    @error('position_id')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>

                <div class="md-field">
                    <label class="md-field__label">
                        Subject Code <span style="color:var(--md-error)">*</span>
                    </label>
                    <select name="subject_code_id" class="md-field__input @error('subject_code_id') md-field--error @enderror" required>
                        <option value="">— Select subject code —</option>
                        @foreach($subjectCodes as $sc)
                            <option value="{{ $sc->id }}" {{ old('subject_code_id') == $sc->id ? 'selected' : '' }}>
                                {{ $sc->code }} — {{ $sc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('subject_code_id')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="md-field" style="margin-bottom:14px;max-width:200px;">
                <label class="md-field__label">
                    Number of Vacancies <span style="color:var(--md-error)">*</span>
                </label>
                <input type="number" name="vacancy_count" min="1" max="999"
                       class="md-field__input @error('vacancy_count') md-field--error @enderror"
                       value="{{ old('vacancy_count', 1) }}" required>
                @error('vacancy_count')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">Reference No.</label>
                <input type="text" name="reference_no"
                       class="md-field__input @error('reference_no') md-field--error @enderror"
                       value="{{ old('reference_no') }}" placeholder="e.g. circular / approval reference" maxlength="60">
                @error('reference_no')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">Remarks</label>
                <textarea name="remarks" rows="3" maxlength="1000"
                          class="md-field__input @error('remarks') md-field--error @enderror"
                          placeholder="Optional">{{ old('remarks') }}</textarea>
                @error('remarks')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div style="background:var(--md-surface-container);border-radius:var(--md-shape-sm);
                        padding:14px 16px;margin-bottom:8px;">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <img src="{{ route('e-signatures.show', auth()->user()->eSignature) }}"
                         alt="Your signature" style="max-height:44px;background:#fff;border-radius:4px;padding:3px;">
                    <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
                        This will be attached as your e-signature on this letter.
                    </span>
                </div>
                <label style="display:flex;align-items:flex-start;gap:8px;font-size:12px;
                              color:var(--md-on-surface-variant);cursor:pointer;">
                    <input type="checkbox" name="confirm_signature" value="1" required style="margin-top:2px;">
                    I confirm I am e-signing this vacancy availability letter, and understand it will
                    remain active for 90 days from today.
                </label>
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('vacancy-availability-letters.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">✓ Issue &amp; E-Sign Letter</button>
        </div>
    </form>
    @endif
</div>

@endsection
