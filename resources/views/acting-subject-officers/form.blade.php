@extends('layouts.app')
@section('title', 'Appoint Acting Subject Officer')
@section('content')

<div style="max-width:560px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('acting-subject-officers.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Appoint Acting Subject Officer</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                Grants a user Subject Officer access for a code they don't permanently hold, for a set period.
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

    @if($users->isEmpty())
        <div class="md-card md-card--elevated" style="padding:32px;text-align:center;color:var(--md-on-surface-variant);">
            No active Subject Officer accounts are available to appoint.
        </div>
    @else
    <form method="POST" action="{{ route('acting-subject-officers.store') }}" class="md-card md-card--elevated">
        @csrf

        <div class="md-card__body">
            <div class="md-field" style="margin-bottom:14px;">
                <label class="md-field__label">
                    Officer <span style="color:var(--md-error)">*</span>
                </label>
                <select name="user_id" class="md-field__input @error('user_id') md-field--error @enderror" required>
                    <option value="">— Select officer —</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->email }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field" style="margin-bottom:14px;">
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

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="md-field">
                    <label class="md-field__label">
                        Start Date <span style="color:var(--md-error)">*</span>
                    </label>
                    <input type="date" name="start_date"
                           class="md-field__input @error('start_date') md-field--error @enderror"
                           value="{{ old('start_date', now()->toDateString()) }}" required>
                    @error('start_date')<div class="md-field__error">{{ $message }}</div>@enderror
                </div>
                <div class="md-field">
                    <label class="md-field__label">End Date</label>
                    <input type="date" name="end_date"
                           class="md-field__input @error('end_date') md-field--error @enderror"
                           value="{{ old('end_date') }}">
                    @error('end_date')<div class="md-field__error">{{ $message }}</div>@enderror
                    <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                        Leave blank for an open-ended appointment until revoked.
                    </div>
                </div>
            </div>

            <div class="md-field">
                <label class="md-field__label">Reason</label>
                <textarea name="reason" rows="3" maxlength="300"
                          class="md-field__input @error('reason') md-field--error @enderror"
                          placeholder="e.g. Covering for Ms. Perera during maternity leave">{{ old('reason') }}</textarea>
                @error('reason')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('acting-subject-officers.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">Appoint Officer</button>
        </div>
    </form>
    @endif
</div>

@endsection
