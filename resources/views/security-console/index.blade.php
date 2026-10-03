@extends('layouts.app')

@section('title', 'Security Console')

@push('styles')
<style>
    .security-console {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .security-hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        flex-wrap: wrap;
        padding: 22px 24px;
        border: 1px solid var(--md-outline-variant);
        border-radius: var(--md-shape-lg, 20px);
        background:
            radial-gradient(circle at 90% 20%, color-mix(in srgb, var(--md-primary) 14%, transparent) 0, transparent 34%),
            var(--md-surface-container-low);
        box-shadow: var(--md-elevation-1);
    }

    .security-hero__title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0 0 6px;
        color: var(--md-on-surface);
    }

    .security-hero__icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--md-primary-container);
        color: var(--md-on-primary-container);
        font-size: 23px;
        flex: 0 0 auto;
    }

    .security-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .security-metric {
        position: relative;
        overflow: hidden;
        min-height: 122px;
        padding: 17px 18px;
        border-radius: var(--md-shape-lg, 18px);
        border: 1px solid var(--md-outline-variant);
        background: var(--md-surface-container-low);
        box-shadow: var(--md-elevation-1);
    }

    .security-metric::after {
        content: '';
        position: absolute;
        width: 70px;
        height: 70px;
        right: -20px;
        bottom: -26px;
        border-radius: 50%;
        background: color-mix(in srgb, var(--md-primary) 9%, transparent);
    }

    .security-metric__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 15px;
    }

    .security-metric__icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--md-secondary-container);
        color: var(--md-on-secondary-container);
        font-size: 17px;
    }

    .security-metric__value {
        font-size: clamp(28px, 3vw, 38px);
        line-height: 1;
        font-weight: 700;
        color: var(--md-on-surface);
        letter-spacing: -1px;
    }

    .security-metric__label {
        margin-top: 7px;
        color: var(--md-on-surface-variant);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .2px;
    }

    .security-notice {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 14px 16px;
        border-radius: var(--md-shape-md, 14px);
        border: 1px solid color-mix(in srgb, var(--md-warning, #b26a00) 30%, var(--md-outline-variant));
        background: color-mix(in srgb, var(--md-warning, #b26a00) 8%, var(--md-surface-container-low));
        color: var(--md-on-surface);
    }

    .security-notice__icon {
        font-size: 19px;
        line-height: 1.2;
    }

    .security-panel {
        border: 1px solid var(--md-outline-variant);
        border-radius: var(--md-shape-lg, 18px);
        background: var(--md-surface-container-low);
        box-shadow: var(--md-elevation-1);
        overflow: hidden;
    }

    .security-panel__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        padding: 16px 18px;
        border-bottom: 1px solid var(--md-outline-variant);
        background: color-mix(in srgb, var(--md-primary) 4%, var(--md-surface-container-low));
    }

    .security-panel__title {
        display: flex;
        align-items: center;
        gap: 9px;
        font-weight: 700;
        color: var(--md-on-surface);
    }

    .security-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .security-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1080px;
    }

    .security-table th {
        text-align: left;
        padding: 11px 14px;
        background: var(--md-surface-container);
        color: var(--md-on-surface-variant);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .55px;
        white-space: nowrap;
        border-bottom: 1px solid var(--md-outline-variant);
    }

    .security-table td {
        padding: 14px;
        vertical-align: top;
        border-bottom: 1px solid var(--md-outline-variant);
        color: var(--md-on-surface);
        font-size: 13px;
    }

    .security-table tbody tr {
        transition: background .16s ease;
    }

    .security-table tbody tr:hover {
        background: color-mix(in srgb, var(--md-primary) 4%, transparent);
    }

    .security-table tbody tr:last-child td {
        border-bottom: none;
    }

    .security-user {
        min-width: 178px;
    }

    .security-user__name {
        font-weight: 700;
        color: var(--md-on-surface);
        margin-bottom: 3px;
    }

    .security-subtext {
        color: var(--md-on-surface-variant);
        font-size: 11px;
        line-height: 1.45;
    }

    .security-device {
        min-width: 190px;
        max-width: 270px;
    }

    .security-device__name {
        font-weight: 600;
        margin-bottom: 4px;
    }

    .security-agent {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        overflow-wrap: anywhere;
    }

    .security-ip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 8px;
        border-radius: 8px;
        background: var(--md-surface-container-high);
        border: 1px solid var(--md-outline-variant);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 11px;
        white-space: nowrap;
    }

    .security-statuses {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        min-width: 110px;
    }

    .security-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .security-badge--online {
        color: #0d6638;
        background: #dff7e8;
        border-color: #b7eac9;
    }

    .security-badge--idle {
        color: #795400;
        background: #fff3cd;
        border-color: #ead48b;
    }

    .security-badge--revoked {
        color: var(--md-on-surface-variant);
        background: var(--md-surface-container-high);
        border-color: var(--md-outline-variant);
    }

    .security-badge--current {
        color: var(--md-on-primary-container);
        background: var(--md-primary-container);
        border-color: color-mix(in srgb, var(--md-primary) 25%, transparent);
    }

    [data-theme="dark"] .security-badge--online {
        color: #adf2c8;
        background: #123d28;
        border-color: #276343;
    }

    [data-theme="dark"] .security-badge--idle {
        color: #ffe08a;
        background: #493a0f;
        border-color: #6f5a1b;
    }

    .security-actions {
        min-width: 245px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .security-inline-action {
        display: grid;
        grid-template-columns: minmax(130px, 1fr) auto;
        gap: 7px;
        align-items: center;
    }

    .security-password {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        border: 1px solid var(--md-outline);
        background: var(--md-surface-container-high);
        color: var(--md-on-surface);
        border-radius: 10px;
        min-height: 38px;
        padding: 8px 10px;
        font: inherit;
        font-size: 12px;
        outline: none;
    }

    .security-password:focus {
        border-color: var(--md-primary);
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--md-primary) 16%, transparent);
    }

    .security-danger-btn,
    .security-warning-btn {
        min-height: 38px;
        border-radius: 999px;
        padding: 8px 13px;
        font: inherit;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: transform .12s ease, filter .12s ease;
    }

    .security-danger-btn:hover,
    .security-warning-btn:hover {
        transform: translateY(-1px);
        filter: brightness(.98);
    }

    .security-danger-btn {
        color: var(--md-error);
        background: transparent;
        border: 1px solid color-mix(in srgb, var(--md-error) 45%, var(--md-outline-variant));
    }

    .security-danger-btn--filled {
        color: var(--md-on-error, #fff);
        background: var(--md-error);
        border-color: var(--md-error);
    }

    .security-warning-btn {
        color: #6d4a00;
        background: #fff3cd;
        border: 1px solid #e3c76c;
    }

    [data-theme="dark"] .security-warning-btn {
        color: #ffe39b;
        background: #46360b;
        border-color: #715918;
    }

    .security-details {
        border: 1px solid var(--md-outline-variant);
        border-radius: 12px;
        background: var(--md-surface-container);
        overflow: hidden;
    }

    .security-details > summary {
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        min-height: 36px;
        padding: 8px 11px;
        cursor: pointer;
        color: var(--md-primary);
        font-size: 11px;
        font-weight: 700;
        user-select: none;
    }

    .security-details > summary::-webkit-details-marker {
        display: none;
    }

    .security-details > summary::after {
        content: '▾';
        transition: transform .15s ease;
    }

    .security-details[open] > summary::after {
        transform: rotate(180deg);
    }

    .security-details__body {
        display: flex;
        flex-direction: column;
        gap: 9px;
        padding: 10px;
        border-top: 1px solid var(--md-outline-variant);
    }

    .security-details__body form {
        display: grid;
        grid-template-columns: minmax(130px, 1fr) auto;
        gap: 7px;
        align-items: center;
    }

    .security-empty {
        text-align: center;
        padding: 34px 18px !important;
        color: var(--md-on-surface-variant) !important;
    }

    .security-event-code {
        display: inline-flex;
        padding: 4px 7px;
        border-radius: 7px;
        background: var(--md-surface-container-high);
        border: 1px solid var(--md-outline-variant);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 10px;
        color: var(--md-on-surface);
    }

    .security-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 12px 16px;
        border-top: 1px solid var(--md-outline-variant);
        background: var(--md-surface-container-low);
    }

    @media (max-width: 1100px) {
        .security-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .security-hero {
            padding: 18px;
        }

        .security-metrics {
            grid-template-columns: 1fr;
        }

        .security-inline-action,
        .security-details__body form {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="security-console">
    <section class="security-hero">
        <div>
            <h2 class="md-headline-sm security-hero__title">
                <span class="security-hero__icon" aria-hidden="true">🛡</span>
                <span>Super Admin Security Console</span>
            </h2>
            <div class="md-body-sm" style="color:var(--md-on-surface-variant);max-width:720px;">
                Monitor authenticated users and sessions, review security events, and perform protected account-security actions.
            </div>
        </div>
        <a href="{{ route('users.index') }}" class="md-btn md-btn--outlined">← User Management</a>
    </section>

    @if(session('success'))
        <div class="md-card" style="padding:12px 15px;border-left:4px solid var(--md-success, #2e7d32);">
            <strong>✓ Completed.</strong> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="md-card" style="padding:12px 15px;border-left:4px solid var(--md-error);">
            <strong>Action failed.</strong> {{ session('error') }}
        </div>
    @endif
    @if(session('info'))
        <div class="md-card" style="padding:12px 15px;border-left:4px solid var(--md-primary);">
            {{ session('info') }}
        </div>
    @endif
    @if($errors->any())
        <div class="md-card" style="padding:12px 15px;border-left:4px solid var(--md-error);">
            <strong>Action not completed.</strong> {{ $errors->first() }}
        </div>
    @endif

    <section class="security-metrics" aria-label="Security overview">
        <article class="security-metric">
            <div class="security-metric__top">
                <span class="security-metric__icon" aria-hidden="true">●</span>
                <span class="md-caption">LAST {{ \App\Services\UserSessionSecurityService::ONLINE_WINDOW_MINUTES }} MIN</span>
            </div>
            <div class="security-metric__value">{{ $onlineCount }}</div>
            <div class="security-metric__label">Online users</div>
        </article>

        <article class="security-metric">
            <div class="security-metric__top">
                <span class="security-metric__icon" aria-hidden="true">▣</span>
                <span class="md-caption">AUTHENTICATED</span>
            </div>
            <div class="security-metric__value">{{ $activeSessionCount }}</div>
            <div class="security-metric__label">Active sessions</div>
        </article>

        <article class="security-metric">
            <div class="security-metric__top">
                <span class="security-metric__icon" aria-hidden="true">⚠</span>
                <span class="md-caption">LAST 24 HOURS</span>
            </div>
            <div class="security-metric__value">{{ $failed24h }}</div>
            <div class="security-metric__label">Failed logins</div>
        </article>

        <article class="security-metric">
            <div class="security-metric__top">
                <span class="security-metric__icon" aria-hidden="true">↪</span>
                <span class="md-caption">LAST 24 HOURS</span>
            </div>
            <div class="security-metric__value">{{ $remoteLogouts24h }}</div>
            <div class="security-metric__label">Remote logout actions</div>
        </article>
    </section>

    <div class="security-notice">
        <span class="security-notice__icon" aria-hidden="true">🔐</span>
        <div class="md-body-sm">
            <strong>Protected administrative actions.</strong>
            Remote logout, force-password-change and MFA-reset require your current Super Admin password.
            A user is shown as online only when authenticated activity occurred within the last
            {{ \App\Services\UserSessionSecurityService::ONLINE_WINDOW_MINUTES }} minutes.
        </div>
    </div>

    <section class="security-panel">
        <div class="security-panel__header">
            <div class="security-panel__title"><span aria-hidden="true">▣</span> Authenticated Sessions</div>
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Current and recently active sessions</span>
        </div>

        <div class="security-table-wrap">
            <table class="security-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>User</th>
                        <th>Device</th>
                        <th>IP address</th>
                        <th>Authenticated</th>
                        <th>Last activity</th>
                        <th>Security controls</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        @php
                            $isCurrent = hash_equals($currentSessionHash, $session->session_hash);
                            $isOnline = !$session->revoked_at && $session->last_activity_at && $session->last_activity_at->gte($onlineCutoff);
                        @endphp
                        <tr>
                            <td>
                                <div class="security-statuses">
                                    @if($session->revoked_at)
                                        <span class="security-badge security-badge--revoked">○ Revoked</span>
                                    @elseif($isOnline)
                                        <span class="security-badge security-badge--online">● Online</span>
                                    @else
                                        <span class="security-badge security-badge--idle">◷ Idle</span>
                                    @endif

                                    @if($isCurrent)
                                        <span class="security-badge security-badge--current">◆ This session</span>
                                    @endif
                                </div>
                            </td>
                            <td class="security-user">
                                <div class="security-user__name">{{ $session->user?->name ?? 'Deleted user' }}</div>
                                <div class="security-subtext">{{ $session->user?->email ?: 'No email available' }}</div>
                            </td>
                            <td class="security-device">
                                <div class="security-device__name">{{ $session->device_label ?: 'Unknown device' }}</div>
                                <div class="security-subtext security-agent" title="{{ $session->user_agent }}">
                                    {{ $session->user_agent ?: 'User agent unavailable' }}
                                </div>
                            </td>
                            <td>
                                <span class="security-ip">⌁ {{ $session->ip_address ?: '—' }}</span>
                            </td>
                            <td>
                                <div>{{ optional($session->authenticated_at)->format('Y-m-d') ?: '—' }}</div>
                                <div class="security-subtext">{{ optional($session->authenticated_at)->format('H:i:s') ?: '' }}</div>
                            </td>
                            <td>
                                <div>{{ optional($session->last_activity_at)->diffForHumans() ?: '—' }}</div>
                                @if($session->last_activity_at)
                                    <div class="security-subtext">{{ $session->last_activity_at->format('H:i:s') }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="security-actions">
                                    @if(!$session->revoked_at && !$isCurrent)
                                        <form method="POST" action="{{ route('security-console.sessions.revoke', $session) }}" class="security-inline-action">
                                            @csrf
                                            @method('PATCH')
                                            <input type="password" name="admin_password" class="security-password"
                                                placeholder="Your admin password" required autocomplete="current-password"
                                                aria-label="Current Super Admin password for remote logout">
                                            <button class="security-danger-btn" onclick="return confirm('Remote logout this session?')">
                                                ↪ Remote Logout
                                            </button>
                                        </form>
                                    @elseif($isCurrent)
                                        <div class="security-subtext" style="padding:4px 2px;">Your current session is protected from remote termination here.</div>
                                    @endif

                                    @if($session->user && $session->user->id !== auth()->id())
                                        <details class="security-details">
                                            <summary>More security actions</summary>
                                            <div class="security-details__body">
                                                <form method="POST" action="{{ route('security-console.users.revoke-all', $session->user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="password" name="admin_password" class="security-password"
                                                        placeholder="Your admin password" required autocomplete="current-password">
                                                    <button class="security-danger-btn security-danger-btn--filled"
                                                        onclick="return confirm('Log out ALL sessions for this user?')">
                                                        Log Out All Sessions
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('security-console.users.force-password-change', $session->user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="password" name="admin_password" class="security-password"
                                                        placeholder="Your admin password" required autocomplete="current-password">
                                                    <button class="security-warning-btn"
                                                        onclick="return confirm('Force password change and revoke sessions?')">
                                                        Force Password Change
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('security-console.users.reset-mfa', $session->user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="password" name="admin_password" class="security-password"
                                                        placeholder="Your admin password" required autocomplete="current-password">
                                                    <button class="security-danger-btn"
                                                        onclick="return confirm('Reset MFA and revoke sessions for this user?')">
                                                        Reset MFA
                                                    </button>
                                                </form>
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="security-empty">
                                No authenticated sessions have been recorded yet. Sessions will appear after users sign in following this update.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="security-footer">
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
                {{ $sessions->total() }} recorded session(s)
            </span>
            {{ $sessions->links('vendor.pagination.material') }}
        </div>
    </section>

    <section class="security-panel">
        <div class="security-panel__header">
            <div class="security-panel__title"><span aria-hidden="true">☷</span> Recent Security Events</div>
            <span class="md-body-sm" style="color:var(--md-on-surface-variant);">Authentication and administrative security activity</span>
        </div>

        <div class="security-table-wrap">
            <table class="security-table" style="min-width:820px;">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Event</th>
                        <th>User</th>
                        <th>IP address</th>
                        <th>Route</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td style="white-space:nowrap;">{{ optional($event->occurred_at)->format('Y-m-d H:i:s') ?: '—' }}</td>
                            <td><span class="security-event-code">{{ $event->event }}</span></td>
                            <td>{{ $event->user?->name ?: 'Unauthenticated / unknown' }}</td>
                            <td><span class="security-ip">⌁ {{ $event->ip_address ?: '—' }}</span></td>
                            <td><span class="security-subtext">{{ $event->route_name ?: '—' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="security-empty">No database-backed security events yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
