@extends('layouts.app')
@section('title', 'Login')
@section('content')
<div class="md-login-wrap">
    <div class="md-login-card">
        <h2 class="md-headline-sm" style="margin-bottom:8px;color:var(--md-primary);">Carder Management</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:28px;">HIMS — Sign in to continue</p>

        @if ($errors->any())
            <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 16px;border-radius:var(--md-shape-sm);margin-bottom:20px;font-size:13px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:18px;">
            @csrf
            @if($showUsername)
                <div class="md-field">
                    <label class="md-field__label">Email</label>
                    <input type="email" name="email" class="md-field__input" value="{{ old('email') }}" required autofocus>
                </div>
            @else
                <div class="md-field">
                    <label class="md-field__label">Group / Category</label>
                    <select id="loginGroup" class="md-field__input" required autofocus>
                        <option value="" disabled selected>Select your group…</option>
                        @foreach($groups as $g)
                            <option value="{{ $g['value'] }}" {{ old('group') === $g['value'] ? 'selected' : '' }}>{{ $g['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md-field" id="loginUserWrap" style="display:none;">
                    <label class="md-field__label">Your Name</label>
                    <select name="user_id" id="loginUser" class="md-field__input" required>
                        <option value="" disabled selected>Select your group first…</option>
                    </select>
                    <div id="loginUserConfirm" style="display:none;padding:11px 14px;background:var(--md-surface-container);border-radius:var(--md-shape-sm);font-size:14px;">
                        Signing in as <strong id="loginUserConfirmName"></strong>
                        <button type="button" id="loginUserConfirmChange" style="background:none;border:none;color:var(--md-primary);font-size:12px;cursor:pointer;margin-left:6px;padding:0;">(not you?)</button>
                    </div>
                    <input type="hidden" name="user_id" id="loginUserHidden" disabled>
                </div>
            @endif
            <div class="md-field">
                <label class="md-field__label">Password</label>
                <div class="pw-wrap">
                    <input type="password" name="password" id="loginPassword" class="md-field__input" required>
                    <button type="button" class="pw-reveal" onclick="togglePw('loginPassword',this)" title="Show/hide password">&#128065;</button>
                </div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--md-on-surface-variant);cursor:pointer;">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="{{ route('forgot-password') }}"
                   style="font-size:13px;color:var(--md-primary);">Forgot password?</a>
            </div>
            <button type="submit" class="md-btn md-btn--filled" style="width:100%;height:44px;">Sign in</button>
        </form>
    </div>
</div>
<script>
function togglePw(id, btn) {
    var el = document.getElementById(id);
    if (!el) return;
    var shown = el.type === 'text';
    el.type = shown ? 'password' : 'text';
    btn.innerHTML = shown ? '&#128065;' : '&#128064;';
    btn.title = shown ? 'Show password' : 'Hide password';
}

@unless($showUsername)
(function () {
    var groupSelect   = document.getElementById('loginGroup');
    var userWrap      = document.getElementById('loginUserWrap');
    var userSelect    = document.getElementById('loginUser');
    var userHidden    = document.getElementById('loginUserHidden');
    var confirmBox    = document.getElementById('loginUserConfirm');
    var confirmName   = document.getElementById('loginUserConfirmName');
    var confirmChange = document.getElementById('loginUserConfirmChange');

    // Exactly one of these three may hold the submitted user_id at any
    // time — disabled fields are excluded from form submission entirely,
    // so toggling .disabled (not just hiding with CSS) is what prevents
    // the select and the hidden input from both submitting user_id and
    // silently relying on "last one wins" form-encoding behaviour.
    function showSelect() {
        userSelect.disabled = false;
        userHidden.disabled = true;
        userSelect.style.display = 'block';
        confirmBox.style.display = 'none';
    }
    function showConfirm(id, name) {
        userHidden.value = id;
        userHidden.disabled = false;
        userSelect.disabled = true;
        userSelect.style.display = 'none';
        confirmName.textContent = name;
        confirmBox.style.display = 'block';
    }

    confirmChange.addEventListener('click', function () {
        // Rare case: exactly one match but it wasn't them (e.g. picked
        // the wrong group by mistake). Re-fetching the SAME group would
        // just show the same single match again — reset the group choice
        // entirely instead, so they can pick a different one.
        groupSelect.value = '';
        userWrap.style.display = 'none';
        userHidden.disabled = true;
        groupSelect.focus();
    });

    groupSelect.addEventListener('change', function () {
        var group = groupSelect.value;
        if (!group) { userWrap.style.display = 'none'; return; }

        showSelect();
        userSelect.disabled = true;
        userSelect.innerHTML = '<option value="" disabled selected>Loading…</option>';
        userWrap.style.display = 'block';

        fetch('{{ route('login.users-for-group') }}?group=' + encodeURIComponent(group))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var users = data.users || [];
                if (users.length === 0) {
                    userSelect.innerHTML = '<option value="" disabled selected>No accounts found in this group</option>';
                    userSelect.disabled = true;
                    return;
                }
                if (users.length === 1) {
                    // Only one account in this group — no point making
                    // them pick from a list of one; confirm and move on.
                    showConfirm(users[0].id, users[0].name);
                    return;
                }
                var html = '<option value="" disabled selected>Select your name…</option>';
                users.forEach(function (u) {
                    html += '<option value="' + u.id + '">' + u.name.replace(/</g, '&lt;') + '</option>';
                });
                userSelect.innerHTML = html;
                showSelect();
            })
            .catch(function () {
                userSelect.innerHTML = '<option value="" disabled selected>Could not load — try again</option>';
                showSelect();
            });
    });
})();
@endunless
</script>
@endsection
