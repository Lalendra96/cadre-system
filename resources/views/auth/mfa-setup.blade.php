@extends('layouts.app')

@section('title', 'Enable Offline OTP')

@section('content')
    <div class="md-card" style="max-width: 560px; margin: 40px auto; padding: 24px;">
        <h1 class="md-headline-sm">
            Enable offline OTP MFA
        </h1>

        <p class="md-body-sm">
            Save this secret in the hospital-approved offline authenticator.
            Keep it confidential.
        </p>

        <code
            style="
            display: block;
            padding: 14px;
            margin: 14px 0;
            background: var(--md-surface-container-high);
            word-break: break-all;
        ">{{ $secret }}</code>

        <form method="POST" action="{{ route('mfa.enable') }}">
            @csrf

            <input type="hidden" name="secret" value="{{ $secret }}">

            <input class="md-input" name="code" inputmode="numeric" maxlength="6"
                placeholder="Enter current six-digit OTP" required>

            <button class="md-btn--primary" style="margin-top: 14px;">
                Enable MFA
            </button>
        </form>
    </div>
@endsection
