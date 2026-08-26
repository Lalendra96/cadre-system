@extends('layouts.app')
@section('title', 'Forgot Password')

@section('content')
<div class="md-login-wrap">
    <div class="md-login-card">

        <a href="{{ route('login') }}"
           style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--md-on-surface-variant);text-decoration:none;margin-bottom:20px;">
            &#8592; Back to sign in
        </a>

        <div style="text-align:center;margin-bottom:24px;">
            <div style="font-size:2.5rem;margin-bottom:8px;">&#128272;</div>
            <h2 class="md-headline-sm" style="margin-bottom:6px;">Forgot your password?</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                Enter your registered email address and your system administrator
                will be notified to reset your account.
            </p>
        </div>

        @if($errors->any())
            <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                        padding:12px 16px;border-radius:var(--md-shape-sm);margin-bottom:20px;font-size:13px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('forgot-password.submit') }}"
              style="display:flex;flex-direction:column;gap:18px;">
            @csrf

            <div class="md-field">
                <label class="md-field__label">Email address</label>
                <input type="email" name="email"
                       class="md-field__input @error('email') md-field--error @enderror"
                       value="{{ old('email') }}" required autofocus
                       placeholder="you@hims.local">
                @error('email')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="md-btn md-btn--filled" style="height:44px;width:100%;justify-content:center;">
                Request Password Reset
            </button>
        </form>

        <div style="margin-top:20px;padding:14px 16px;background:var(--md-surface-container);
                    border-radius:var(--md-shape-sm);font-size:12px;color:var(--md-on-surface-variant);">
            &#8505;&nbsp; This is an internal system. If you do not receive a response within a reasonable
            time, contact your system administrator directly.
        </div>

    </div>
</div>
@endsection
