@extends('layouts.app')

@section('title', 'Offline OTP Verification')

@section('content')
    <div class="md-card" style="max-width: 440px; margin: 60px auto; padding: 24px;">
        <h1 class="md-headline-sm">
            Offline OTP verification
        </h1>

        <p class="md-body-sm" style="margin: 10px 0 18px;">
            Open your approved authenticator device and enter the current
            six-digit code. No internet connection is required.
        </p>

        <form method="POST" action="{{ route('mfa.verify') }}">
            @csrf

            <input class="md-input" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000"
                required autofocus>

            <button class="md-btn--primary" style="margin-top: 14px; width: 100%;">
                Verify and continue
            </button>
        </form>
    </div>
@endsection
