<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Carder Management') — HIMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/md3-dark.css') }}">
    <style>
    /* ── Legacy MD2 class compatibility layer ────────────────────────────────
       These aliases ensure views using old class names still render correctly.
       Fixes 99 occurrences across 47 view files without requiring a full rewrite.
       Remove incrementally as views are migrated to MD3 class names.           ── */

    /* Inputs & selects */
    .md-input, select.md-select { all: unset; display: block; width: 100%; box-sizing: border-box;
        padding: 10px 14px; font-size: 14px; border-radius: var(--md-shape-xs, 4px);
        background: var(--md-surface-container-high); color: var(--md-on-surface);
        border: 1px solid var(--md-outline); outline: none; transition: border-color .15s; }
    .md-input:focus, select.md-select:focus { border-color: var(--md-primary); }
    .md-input.md-input--error, select.md-select.md-input--error { border-color: var(--md-error); }

    /* Labels */
    .md-label { display: block; font-size: 12px; font-weight: 500; letter-spacing: .4px;
        color: var(--md-on-surface-variant); margin-bottom: 4px; }
    label.md-label.md-label--required::after { content: ' *'; color: var(--md-error); }

    /* Layout helpers */
    .md-form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px; margin-bottom: 14px; }
    .md-form-group { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
    .md-check-group { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }

    /* Error & hint text */
    .md-field-error { color: var(--md-error); font-size: 11px; margin-top: 3px; }
    .md-field-hint  { color: var(--md-on-surface-variant); font-size: 11px; margin-top: 3px; }

    /* Buttons */
    .md-btn--primary { background: var(--md-primary); color: var(--md-on-primary);
        border: none; padding: 10px 20px; border-radius: var(--md-shape-full, 999px);
        font-size: 14px; font-weight: 500; cursor: pointer; letter-spacing: .1px; }
    .md-btn--primary:hover { filter: brightness(1.1); }
    .md-btn--ghost { background: transparent; color: var(--md-primary);
        border: 1px solid var(--md-outline); padding: 9px 20px;
        border-radius: var(--md-shape-full, 999px); font-size: 14px;
        font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex;
        align-items: center; }
    .md-btn--ghost:hover { background: color-mix(in srgb, var(--md-primary) 8%, transparent); }

    /* Card body */
    .md-card__body { padding: 20px; }
    .md-card__footer { padding: 12px 20px; border-top: 1px solid var(--md-outline-variant);
        display: flex; justify-content: flex-end; gap: 10px; }

    /* Typography */
    .md-h2 { font-size: 24px; font-weight: 500; color: var(--md-on-surface); margin-bottom: 16px; }
    .md-h3 { font-size: 18px; font-weight: 500; color: var(--md-on-surface); margin-bottom: 12px; }
    .md-caption { font-size: 11px; color: var(--md-on-surface-variant); letter-spacing: .4px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @stack('head')
</head>
<body>
    @auth
    <header class="md-appbar" style="position:sticky;top:0;z-index:100;">
        <button id="nav-toggle" title="Toggle navigation" aria-label="Toggle navigation">&#9776;</button>
        <span class="md-title-lg" style="flex:1;color:var(--md-on-surface);">Carder Management System</span>

        {{-- ── Notification bell ──────────────────────────────────────────── --}}
        <div style="position:relative;margin-right:12px;">
            <button id="notif-bell-btn" title="Notifications" aria-label="Notifications"
                    style="position:relative;background:none;border:none;cursor:pointer;
                           font-size:20px;color:var(--md-on-surface-variant);padding:6px 10px;
                           border-radius:50%;line-height:1;">
                🔔
                <span id="notif-badge"
                      style="display:none;position:absolute;top:2px;right:4px;
                             background:var(--md-error);color:#fff;font-size:10px;
                             font-weight:700;min-width:16px;height:16px;border-radius:8px;
                             align-items:center;justify-content:center;padding:0 3px;
                             line-height:16px;text-align:center;">0</span>
            </button>

            <div id="notif-dropdown"
                 style="display:none;position:absolute;right:0;top:44px;width:340px;
                        max-height:420px;overflow-y:auto;background:var(--md-surface-container-low);
                        border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-md);
                        box-shadow:var(--md-elevation-3);z-index:200;">
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:10px 14px;border-bottom:1px solid var(--md-outline-variant);">
                    <span class="md-label-md">Notifications</span>
                    <button id="notif-mark-all" type="button"
                            style="background:none;border:none;color:var(--md-primary);
                                   font-size:11px;cursor:pointer;">Mark all read</button>
                </div>
                <div id="notif-list"></div>
            </div>
        </div>

        <span class="md-label-md" style="color:var(--md-on-surface-variant);margin-right:12px;">
            {{ auth()->user()->name }} &middot; {{ auth()->user()->role_label }}
        </span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="md-btn md-btn--text" style="height:36px;padding:0 16px;">Logout</button>
        </form>
    </header>

    <div style="display:flex;min-height:calc(100vh - 64px);">
        <nav class="md-nav-drawer" id="mainNav" style="position:sticky;top:64px;height:calc(100vh - 64px);overflow-y:auto;flex-shrink:0;">
            @php($user = auth()->user())
            @php($visibleNavItems = \App\Models\NavItem::active()->orderBy('section')->orderBy('sort_order')->get()->filter(fn ($i) => $i->isVisibleTo($user)))
            @php($groupedNavItems = $visibleNavItems->groupBy(fn ($i) => $i->section ?? '__top__'))

            {{-- Ungrouped (top-of-nav) items first, if any --}}
            @foreach($groupedNavItems->get('__top__', collect()) as $item)
                <a href="{{ route($item->route_name) }}"
                   @if($item->open_in_new_tab) target="_blank" @endif
                   class="md-nav-item {{ request()->routeIs($item->route_name) || request()->routeIs($item->route_name . '.*') ? 'md-nav-item--active' : '' }}">
                    {{ $item->label }}
                </a>
            @endforeach

            {{-- Sectioned items, each under its own header --}}
            @foreach($groupedNavItems as $section => $sectionItems)
                @continue($section === '__top__')
                <div class="md-label-sm" style="padding:16px 16px 4px;color:var(--md-on-surface-variant);text-transform:uppercase;letter-spacing:1px;">{{ $section }}</div>
                @foreach($sectionItems as $item)
                    @if($item->route_name === 'users.index')
                        {{-- Special case: live pending-password-reset count badge.
                             Not a permission rule — nav_items has no concept of a
                             dynamic badge, so this one route is special-cased here
                             rather than over-generalizing the schema for it. --}}
                        <a href="{{ route('users.index') }}" class="md-nav-item {{ request()->routeIs('users.*') ? 'md-nav-item--active' : '' }}" style="justify-content:space-between;">
                            <span>{{ $item->label }}</span>
                            @php($pendingResets = \App\Models\User::whereNotNull('password_reset_requested_at')->count())
                            @if($pendingResets > 0)
                                <span style="background:var(--md-error);color:var(--md-on-error);font-size:10px;font-weight:700;padding:2px 6px;border-radius:999px;min-width:18px;text-align:center;">{{ $pendingResets }}</span>
                            @endif
                        </a>
                    @else
                        <a href="{{ route($item->route_name) }}"
                           @if($item->open_in_new_tab) target="_blank" @endif
                           class="md-nav-item {{ request()->routeIs($item->route_name) || request()->routeIs($item->route_name . '.*') ? 'md-nav-item--active' : '' }}">
                            {{ $item->label }}
                        </a>
                    @endif
                @endforeach
            @endforeach
        </nav>

        <main style="flex:1;padding:24px;overflow-x:hidden;">
            @if(session('success'))
                <div class="md-snackbar" style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:200;background:var(--md-success-container);color:var(--md-on-success);">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="md-snackbar" style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:200;background:var(--md-error-container);color:var(--md-on-error-container);">
                    {{ session('error') }}
                </div>
            @endif

            @include('partials.decision-support-notice')

            @yield('content')
        </main>
    </div>
    @else
        @yield('content')
    @endauth

    @stack('scripts')
<script>
(function () {
    const NAV_KEY = 'carder_nav_collapsed';
    const nav     = document.getElementById('mainNav');
    const toggle  = document.getElementById('nav-toggle');
    if (!nav || !toggle) return;

    function setCollapsed(collapsed) {
        if (collapsed) {
            nav.classList.add('nav--collapsed');
            toggle.setAttribute('aria-expanded', 'false');
        } else {
            nav.classList.remove('nav--collapsed');
            toggle.setAttribute('aria-expanded', 'true');
        }
        try { localStorage.setItem(NAV_KEY, collapsed ? '1' : '0'); } catch (_) {}
    }

    // Restore persisted state on page load
    let stored = false;
    try { stored = localStorage.getItem(NAV_KEY) === '1'; } catch (_) {}
    setCollapsed(stored);

    toggle.addEventListener('click', function () {
        setCollapsed(!nav.classList.contains('nav--collapsed'));
    });
})();
</script>

@auth
<script>
/**
 * Minimal live-notification bell — plain JS, no framework, no websocket
 * server. Polls /notifications/unread-count every 30s and only fetches
 * the full list when the user actually opens the dropdown, to keep the
 * background request as cheap as possible on a shared hospital LAN.
 */
(function () {
    'use strict';

    const POLL_INTERVAL_MS = 30000;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || '{{ csrf_token() }}';

    const bellBtn   = document.getElementById('notif-bell-btn');
    const badge     = document.getElementById('notif-badge');
    const dropdown  = document.getElementById('notif-dropdown');
    const listEl    = document.getElementById('notif-list');
    const markAllBtn = document.getElementById('notif-mark-all');

    if (!bellBtn || !badge || !dropdown || !listEl) return;

    function setBadge(count) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    function pollUnreadCount() {
        fetch('{{ route('notifications.unread-count') }}', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) { if (data) setBadge(data.count); })
            .catch(function () { /* silent — a failed poll is not worth alarming the user over */ });
    }

    function timeAgo(iso) {
        const diffSec = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
        if (diffSec < 60) return 'just now';
        if (diffSec < 3600) return Math.floor(diffSec / 60) + 'm ago';
        if (diffSec < 86400) return Math.floor(diffSec / 3600) + 'h ago';
        return Math.floor(diffSec / 86400) + 'd ago';
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function renderList(notifications) {
        if (!notifications.length) {
            listEl.innerHTML = '<div style="padding:24px;text-align:center;color:var(--md-on-surface-variant);font-size:12px;">No notifications yet.</div>';
            return;
        }

        listEl.innerHTML = notifications.map(function (n) {
            const unreadDot = n.is_read ? '' :
                '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--md-primary);margin-right:6px;"></span>';
            const bg = n.is_read ? 'transparent' : 'color-mix(in srgb, var(--md-primary) 6%, transparent)';

            return '<div class="notif-item" data-id="' + n.id + '" data-link="' + escapeHtml(n.link_url || '') + '" ' +
                'style="padding:10px 14px;border-bottom:1px solid var(--md-outline-variant);cursor:pointer;background:' + bg + ';">' +
                '<div style="font-size:12.5px;font-weight:600;color:var(--md-on-surface);">' + unreadDot + escapeHtml(n.title) + '</div>' +
                (n.body ? '<div style="font-size:11.5px;color:var(--md-on-surface-variant);margin-top:2px;">' + escapeHtml(n.body) + '</div>' : '') +
                '<div style="font-size:10px;color:var(--md-on-surface-variant);margin-top:4px;">' + timeAgo(n.created_at) + '</div>' +
                '</div>';
        }).join('');

        // Delegate click on each item: mark read, then navigate if a link exists.
        listEl.querySelectorAll('.notif-item').forEach(function (item) {
            item.addEventListener('click', function () {
                const id   = item.getAttribute('data-id');
                const link = item.getAttribute('data-link');

                fetch('/notifications/' + id + '/read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                }).finally(function () {
                    if (link) window.location.href = link;
                });
            });
        });
    }

    function loadDropdown() {
        listEl.innerHTML = '<div style="padding:20px;text-align:center;color:var(--md-on-surface-variant);font-size:12px;">Loading…</div>';

        fetch('{{ route('notifications.index') }}', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                renderList(data.notifications || []);
                setBadge(data.unread_count || 0);
            })
            .catch(function () {
                listEl.innerHTML = '<div style="padding:20px;text-align:center;color:var(--md-error);font-size:12px;">Could not load notifications.</div>';
            });
    }

    bellBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = dropdown.style.display === 'block';
        dropdown.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) loadDropdown();
    });

    // Close the dropdown when clicking anywhere outside it.
    document.addEventListener('click', function (e) {
        if (dropdown.style.display === 'block' && !dropdown.contains(e.target) && e.target !== bellBtn) {
            dropdown.style.display = 'none';
        }
    });

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            fetch('{{ route('notifications.read-all') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            }).then(function () {
                setBadge(0);
                loadDropdown();
            });
        });
    }

    // Initial check on page load, then poll every 30s.
    pollUnreadCount();
    setInterval(pollUnreadCount, POLL_INTERVAL_MS);
})();
</script>
@endauth

</body>
</html>
