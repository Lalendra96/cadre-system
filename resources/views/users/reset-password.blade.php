@extends('layouts.app')
@section('title', 'Reset Password — ' . $user->name)

@section('content')
<div style="max-width:540px;">

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="{{ route('users.index') }}" class="md-btn md-btn--text md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm" style="margin-bottom:2px;">Reset Temporary Password</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $user->name }} &middot; {{ $user->email }}</p>
        </div>
    </div>

    @if(session('temp_password'))
        <div style="background:var(--md-success-container);color:var(--md-on-success-container);padding:16px 20px;border-radius:var(--md-shape-md);margin-bottom:20px;border:1px solid var(--md-success);">
            <div class="md-title-sm" style="margin-bottom:6px;">&#9989; Temporary password set</div>
            <p class="md-body-sm" style="margin-bottom:10px;">Share this temporary password with the user. It will <strong>not</strong> be shown again.</p>
            <div style="display:flex;align-items:center;gap:10px;background:var(--md-surface-container);border-radius:var(--md-shape-sm);padding:12px 16px;">
                <code id="tempPwDisplay" style="flex:1;font-size:16px;letter-spacing:2px;font-family:monospace;color:var(--md-primary);">{{ session('temp_password') }}</code>
                <button class="md-btn md-btn--tonal md-btn--sm" onclick="copyTempPw()">&#128203; Copy</button>
            </div>
            <p class="md-body-sm" style="margin-top:10px;opacity:.8;">The user will be forced to choose a new password immediately after logging in with this one.</p>
        </div>
    @endif

    <div class="md-card md-card--elevated">
        <div class="md-card__header">
            <span class="md-title-md">Set Temporary Password</span>
        </div>
        <form method="POST" action="{{ route('users.reset-password.update', $user) }}">
            @csrf
            <div class="md-card__body" style="display:flex;flex-direction:column;gap:18px;">

                @if($errors->any())
                    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 16px;border-radius:var(--md-shape-sm);font-size:13px;">
                        @foreach($errors->all() as $e)<p style="margin:2px 0;">{{ $e }}</p>@endforeach
                    </div>
                @endif

                {{-- Auto-generate toggle --}}
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px 0;">
                    <input type="checkbox" id="autoGenerate" name="auto_generate" value="1" checked onchange="toggleAutoGenerate(this)">
                    <span class="md-label-lg">Auto-generate a secure temporary password</span>
                </label>

                <div id="manualPasswordFields" style="display:none;flex-direction:column;gap:14px;">
                    <div class="md-field">
                        <label class="md-field__label">Temporary Password</label>
                        <input type="text" name="password" class="md-field__input @error('password') md-field--error @enderror"
                               placeholder="Min. 8 characters" autocomplete="off">
                        @error('password')<div class="md-field__error">{{ $message }}</div>@enderror
                    </div>
                    <div class="md-field">
                        <label class="md-field__label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="md-field__input">
                    </div>
                </div>

                <div class="md-body-sm" style="background:var(--md-surface-container);padding:12px 16px;border-radius:var(--md-shape-sm);color:var(--md-on-surface-variant);">
                    &#8505;&nbsp;
                    The user's current session will be invalidated and they will be required to log in again. On next login, they will be immediately prompted to set a permanent password of their own before accessing any other section.
                </div>

            </div>
            <div class="md-card__footer">
                <a href="{{ route('users.index') }}" class="md-btn md-btn--text">Cancel</a>
                <button type="submit" class="md-btn md-btn--filled" style="background:var(--md-warning);color:var(--md-on-warning);">
                    &#128274; Set Temporary Password
                </button>
            </div>
        </form>
    </div>

    @if($user->force_password_change)
        <div style="margin-top:16px;padding:12px 16px;border-radius:var(--md-shape-sm);background:var(--md-warning-container);color:var(--md-on-warning-container);font-size:13px;">
            &#9888;&nbsp; This user currently has an <strong>uncleared</strong> temporary password — they have not yet logged in and changed it.
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function toggleAutoGenerate(cb) {
    var fields = document.getElementById('manualPasswordFields');
    fields.style.display = cb.checked ? 'none' : 'flex';
    fields.querySelectorAll('input').forEach(function(el){
        el.required = !cb.checked;
    });
}

function copyTempPw() {
    var text = document.getElementById('tempPwDisplay')?.textContent?.trim();
    if (text && navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function(){
            alert('Copied to clipboard.');
        });
    }
}
</script>
@endpush
