@extends('layouts.app')
@section('title', 'Request Submitted')

@section('content')
<div class="md-login-wrap">
    <div class="md-login-card" style="text-align:center;">

        <div style="font-size:3rem;margin-bottom:16px;">&#9989;</div>
        <h2 class="md-headline-sm" style="margin-bottom:10px;">Request received</h2>
        <p class="md-body-md" style="color:var(--md-on-surface-variant);margin-bottom:6px;">
            If an active account is registered for
        </p>
        <p style="font-weight:600;color:var(--md-primary);margin-bottom:16px;word-break:break-all;">
            {{ $email }}
        </p>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:28px;">
            your system administrator has been notified and will reset your password shortly.
            You will receive your temporary password through the usual internal channel.
        </p>

        <a href="{{ route('login') }}" class="md-btn md-btn--outlined" style="width:100%;justify-content:center;height:44px;">
            Return to sign in
        </a>

        <div style="margin-top:20px;padding:14px 16px;background:var(--md-surface-container);
                    border-radius:var(--md-shape-sm);font-size:12px;color:var(--md-on-surface-variant);">
            &#8505;&nbsp; If you don't hear back, contact your system administrator directly.
        </div>

    </div>
</div>
@endsection
