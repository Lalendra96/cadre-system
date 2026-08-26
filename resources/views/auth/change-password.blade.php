@extends('layouts.app')
@section('title', 'Set New Password')

@section('content')
<div class="md-login-wrap">
    <div class="md-login-card" style="width:420px;">

        <div style="text-align:center;margin-bottom:24px;">
            <div style="font-size:2.5rem;margin-bottom:8px;">&#128274;</div>
            <h2 class="md-headline-sm" style="margin-bottom:6px;color:var(--md-warning);">Password Reset Required</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
                A temporary password was set for your account by the system administrator.
                You must choose a new password before you can continue.
            </p>
        </div>

        @if(session('info'))
            <div style="background:var(--md-warning-container);color:var(--md-on-warning-container);padding:12px 16px;border-radius:var(--md-shape-sm);margin-bottom:20px;font-size:13px;">
                {{ session('info') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 16px;border-radius:var(--md-shape-sm);margin-bottom:20px;font-size:13px;">
                <ul style="margin:0;padding-left:16px;">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('change-password.update') }}" style="display:flex;flex-direction:column;gap:18px;">
            @csrf

            <div class="md-field">
                <label class="md-field__label">Current (Temporary) Password <span style="color:var(--md-error)">*</span></label>
                <div class="pw-wrap"><input type="password" name="current_password" id="pwCurrent" class="md-field__input @error('current_password') md-field--error @enderror" required autocomplete="current-password"><button type="button" class="pw-reveal" onclick="togglePw('pwCurrent',this)" title="Show/hide">&#128065;</button></div>
                <div class="md-field__error">@error('current_password'){{ $message }}@enderror</div>
            </div>

            <hr class="md-divider">

            <div class="md-field">
                <label class="md-field__label">New Password <span style="color:var(--md-error)">*</span></label>
                <div class="pw-wrap"><input type="password" name="password" id="newPassword" class="md-field__input @error('password') md-field--error @enderror" required autocomplete="new-password"><button type="button" class="pw-reveal" onclick="togglePw('newPassword',this)" title="Show/hide">&#128065;</button></div>
                @error('password')<div class="md-field__error">{{ $message }}</div>@enderror
            </div>

            <div class="md-field">
                <label class="md-field__label">Confirm New Password <span style="color:var(--md-error)">*</span></label>
                <div class="pw-wrap"><input type="password" name="password_confirmation" id="pwConfirm" class="md-field__input" required autocomplete="new-password"><button type="button" class="pw-reveal" onclick="togglePw('pwConfirm',this)" title="Show/hide">&#128065;</button></div>
            </div>

            {{-- Live password strength indicator --}}
            <div id="strengthBar" style="height:4px;border-radius:2px;background:var(--md-outline-variant);overflow:hidden;">
                <div id="strengthFill" style="height:100%;width:0;border-radius:2px;transition:width .3s ease,background .3s ease;"></div>
            </div>
            <p id="strengthLabel" class="md-body-sm" style="margin-top:-10px;color:var(--md-on-surface-variant);font-size:11px;"></p>

            <div class="md-body-sm" style="background:var(--md-surface-container);padding:12px;border-radius:var(--md-shape-sm);color:var(--md-on-surface-variant);">
                Password requirements:
                <ul style="margin:6px 0 0;padding-left:16px;line-height:1.8;">
                    <li>At least 8 characters</li>
                    <li>Upper and lower case letters</li>
                    <li>At least one number</li>
                </ul>
            </div>

            <button type="submit" class="md-btn md-btn--filled" style="height:44px;width:100%;justify-content:center;">
                Set New Password &amp; Continue
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" style="margin-top:12px;text-align:center;">
            @csrf
            <button class="md-btn md-btn--text" style="font-size:13px;">Sign out instead</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const input = document.getElementById('newPassword');
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    if (!input) return;

    window.togglePw=function(id,btn){var el=document.getElementById(id);if(!el)return;var s=el.type==='text';el.type=s?'password':'text';btn.innerHTML=s?'&#128065;':'&#128064;';};
    input.addEventListener('input', function(){
        const val = input.value;
        let score = 0;
        if (val.length >= 8)  score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const configs = [
            { w:'0%',   color:'transparent', text:'' },
            { w:'25%',  color:'#FFB4AB',     text:'Weak' },
            { w:'50%',  color:'#F5C56B',     text:'Fair' },
            { w:'75%',  color:'#A8C8FF',     text:'Good' },
            { w:'100%', color:'#7DD996',     text:'Strong' },
        ];
        const c = configs[Math.min(score, 4)];
        fill.style.width     = c.w;
        fill.style.background = c.color;
        label.textContent    = c.text;
        label.style.color    = c.color;
    });
})();
</script>
@endpush
