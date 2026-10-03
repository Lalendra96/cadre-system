@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('content')
    <style>
        .analytics-shell {
            display: grid;
            gap: 16px;
        }

        .analytics-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 18px;
            background: var(--md-surface-container-low, #ffffff);
            border: 1px solid var(--md-outline-variant, #d6e0ea);
            border-radius: 16px;
        }

        .analytics-header h1 {
            margin: 0;
            font-size: 24px;
            color: var(--md-on-surface, #17324d);
        }

        .analytics-header p {
            margin: 6px 0 0;
            color: var(--md-on-surface-variant, #60758b);
            font-size: 13px;
        }

        .analytics-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .dashboard-btn {
            border: 1px solid var(--md-outline-variant, #bfd0df);
            background: var(--md-surface, #ffffff);
            color: var(--md-primary, #0d67b5);
            border-radius: 10px;
            padding: 9px 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .dashboard-btn--primary {
            background: var(--md-primary, #0d67b5);
            color: var(--md-on-primary, #ffffff);
            border-color: var(--md-primary, #0d67b5);
        }

        .governance-banner {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--md-primary-container, #d9e9f8);
            color: var(--md-on-primary-container, #163b5e);
            font-size: 12px;
            line-height: 1.55;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 14px;
            align-items: start;
        }

        .dashboard-widget {
            grid-column: span 6;
            background: var(--md-surface-container-lowest, #ffffff);
            border: 1px solid var(--md-outline-variant, #d6e0ea);
            border-radius: 14px;
            overflow: hidden;
            min-width: 0;
            box-shadow: 0 2px 8px rgba(28, 56, 85, 0.05);
        }

        .dashboard-widget[data-size="wide"] {
            grid-column: span 12;
        }

        .dashboard-widget[data-widget-key="quick_links"] {
            grid-column: 1 / -1;
            border-color: color-mix(in srgb, var(--md-primary, #0d67b5) 28%, var(--md-outline-variant, #d6e0ea));
            box-shadow: 0 5px 18px rgba(13, 103, 181, 0.08);
        }

        .dashboard-widget[data-widget-key="quick_links"] .widget-header {
            cursor: default;
            background: color-mix(in srgb, var(--md-primary-container, #d9e9f8) 48%, transparent);
        }

        .dashboard-widget[data-widget-key="quick_links"] .widget-header:active {
            cursor: default;
        }

        .dashboard-widget.is-dragging {
            opacity: 0.45;
            outline: 2px dashed var(--md-primary, #0d67b5);
        }

        .widget-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--md-outline-variant, #e0e7ef);
            cursor: grab;
            user-select: none;
        }

        .widget-header:active {
            cursor: grabbing;
        }

        .widget-title {
            margin: 0;
            font-size: 14px;
            color: var(--md-on-surface, #193653);
        }

        .widget-subtitle {
            margin: 3px 0 0;
            font-size: 11px;
            color: var(--md-on-surface-variant, #6d8093);
        }

        .widget-tools {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .widget-tool {
            border: 0;
            background: transparent;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            cursor: pointer;
            color: var(--md-on-surface-variant, #60758b);
            font-size: 16px;
        }

        .widget-tool:hover {
            background: var(--md-surface-container, #edf3f8);
        }

        .widget-body {
            padding: 14px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .kpi-card {
            padding: 13px;
            border-radius: 12px;
            background: var(--md-surface-container-low, #f5f8fb);
            border: 1px solid var(--md-outline-variant, #dde6ee);
        }

        .kpi-label {
            font-size: 11px;
            color: var(--md-on-surface-variant, #667a8e);
        }

        .kpi-value {
            margin-top: 5px;
            font-size: 23px;
            font-weight: 800;
            color: var(--md-on-surface, #18344f);
        }

        .metric-list {
            display: grid;
            gap: 10px;
        }

        .metric-row {
            display: grid;
            grid-template-columns: minmax(120px, 1fr) 2fr auto;
            align-items: center;
            gap: 10px;
            font-size: 12px;
        }

        .metric-track {
            height: 8px;
            border-radius: 999px;
            background: var(--md-surface-container-high, #e6edf4);
            overflow: hidden;
        }

        .metric-fill {
            height: 100%;
            border-radius: inherit;
            background: var(--md-primary, #1976d2);
        }

        .metric-value {
            font-weight: 800;
            color: var(--md-on-surface, #193653);
            min-width: 42px;
            text-align: right;
        }

        .mini-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .mini-table th,
        .mini-table td {
            padding: 8px 7px;
            border-bottom: 1px solid var(--md-outline-variant, #e1e8ef);
            text-align: left;
            vertical-align: top;
        }

        .mini-table th {
            color: var(--md-on-surface-variant, #62778b);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status-pills {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 9px;
        }

        .status-pill {
            padding: 11px;
            border: 1px solid var(--md-outline-variant, #dce5ed);
            border-radius: 11px;
            background: var(--md-surface-container-low, #f7f9fb);
        }

        .status-pill strong {
            display: block;
            font-size: 20px;
            color: var(--md-on-surface, #193653);
        }

        .status-pill span {
            font-size: 11px;
            color: var(--md-on-surface-variant, #667a8e);
        }

        .empty-dashboard {
            grid-column: 1 / -1;
            border: 1px dashed var(--md-outline, #93a9bd);
            border-radius: 14px;
            padding: 32px;
            text-align: center;
            color: var(--md-on-surface-variant, #667a8e);
            background: var(--md-surface-container-low, #f8fafc);
        }

        .widget-drawer-backdrop {
            position: fixed;
            inset: 0;
            z-index: 600;
            background: rgba(5, 25, 45, 0.42);
            display: none;
        }

        .widget-drawer-backdrop.is-open {
            display: block;
        }

        .widget-drawer {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: min(460px, 94vw);
            background: var(--md-surface, #ffffff);
            padding: 18px;
            overflow-y: auto;
            box-shadow: -10px 0 30px rgba(0, 20, 40, 0.16);
        }

        .widget-drawer-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 15px;
        }

        .widget-choice-list {
            display: grid;
            gap: 10px;
        }

        .widget-choice {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 12px;
            border: 1px solid var(--md-outline-variant, #d7e1e9);
            border-radius: 12px;
            background: var(--md-surface-container-lowest, #ffffff);
        }

        .widget-choice h3 {
            margin: 0;
            font-size: 13px;
        }

        .widget-choice p {
            margin: 4px 0 0;
            font-size: 11px;
            color: var(--md-on-surface-variant, #667a8e);
        }

        .save-state {
            font-size: 11px;
            min-height: 17px;
            color: var(--md-on-surface-variant, #667a8e);
        }

        @media (max-width: 980px) {

            .dashboard-widget,
            .dashboard-widget[data-size="wide"] {
                grid-column: span 12;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {

            .kpi-grid,
            .status-pills {
                grid-template-columns: 1fr;
            }

            .metric-row {
                grid-template-columns: 1fr auto;
            }

            .metric-track {
                grid-column: 1 / -1;
            }
        }
    </style>

    <div class="analytics-shell">
        <section class="analytics-header">
            <div>
                <h1>{{ $title }}</h1>
                <p>{{ $subtitle }}</p>
                <p>
                    Updated {{ $generatedAt->format('d M Y, H:i') }} · Dashboard values are read-only decision-support
                    information.
                </p>
            </div>

            <div>
                <div class="analytics-actions">
                    <button type="button" class="dashboard-btn" id="open-widget-drawer">
                        + Add widgets
                    </button>

                    <form method="POST" action="{{ route('dashboard.layout.reset') }}"
                        onsubmit="return confirm('Restore the default dashboard for your role?');">
                        @csrf
                        <button type="submit" class="dashboard-btn">
                            Reset layout
                        </button>
                    </form>

                    <button type="button" class="dashboard-btn dashboard-btn--primary" id="save-dashboard-layout">
                        Save dashboard
                    </button>
                </div>
                <div id="dashboard-save-state" class="save-state" aria-live="polite"></div>
            </div>
        </section>

        <div class="governance-banner">
            <span aria-hidden="true">🛡️</span>
            <div>
                <strong>Governed analytics.</strong>
                Widgets are limited by your effective role and scope. Dashboard cards do not grant new permissions, do not
                make automated employment decisions, and do not replace the authorised source record or approval workflow.
            </div>
        </div>

        <section class="dashboard-grid" id="dashboard-grid" aria-label="Customisable analytics dashboard">
            @foreach ($layout as $widgetKey)
                @php
                    $widget = $catalog[$widgetKey] ?? null;
                @endphp
                @continue($widget === null)

                <article class="dashboard-widget" data-widget-key="{{ $widgetKey }}" data-size="{{ $widget['size'] }}"
                    data-pinned-top="{{ $widgetKey === 'quick_links' ? 'true' : 'false' }}"
                    draggable="{{ $widgetKey === 'quick_links' ? 'false' : 'true' }}">
                    <header class="widget-header">
                        <div>
                            <h2 class="widget-title">{{ $widget['title'] }}</h2>
                            <p class="widget-subtitle">{{ $widget['description'] }}</p>
                            @if ($widgetKey === 'quick_links')
                                <span
                                    style="display:inline-flex; margin-top:6px; padding:3px 7px; border-radius:999px; background:var(--md-primary-container,#d9e9f8); color:var(--md-on-primary-container,#163b5e); font-size:10px; font-weight:800;">
                                    Pinned to top
                                </span>
                            @endif
                        </div>
                        <div class="widget-tools">
                            <button type="button" class="widget-tool" data-remove-widget="{{ $widgetKey }}"
                                title="Remove from my dashboard" aria-label="Remove {{ $widget['title'] }}">
                                ×
                            </button>
                        </div>
                    </header>

                    <div class="widget-body">
                        @includeIf('dashboard.widgets.' . $widgetKey)
                    </div>
                </article>
            @endforeach

            <div id="empty-dashboard" class="empty-dashboard" @if (count($layout) > 0) hidden @endif>
                <strong>No widgets are currently shown.</strong>
                <div style="margin-top: 8px;">
                    Use <strong>Add widgets</strong> to restore information available to your role.
                </div>
            </div>
        </section>
    </div>

    <div class="widget-drawer-backdrop" id="widget-drawer-backdrop" aria-hidden="true">
        <aside class="widget-drawer" role="dialog" aria-modal="true" aria-labelledby="widget-drawer-title">
            <div class="widget-drawer-head">
                <div>
                    <h2 id="widget-drawer-title" style="margin: 0; font-size: 18px;">
                        Available widgets
                    </h2>
                    <p style="margin: 4px 0 0; font-size: 12px; color: var(--md-on-surface-variant);">
                        Only widgets authorised for your current role are listed.
                    </p>
                </div>
                <button type="button" class="widget-tool" id="close-widget-drawer" aria-label="Close widget picker">
                    ×
                </button>
            </div>

            <div class="widget-choice-list">
                @foreach ($catalog as $widgetKey => $widget)
                    <div class="widget-choice" data-widget-choice="{{ $widgetKey }}">
                        <div>
                            <h3>{{ $widget['title'] }}</h3>
                            <p>{{ $widget['description'] }}</p>
                        </div>
                        <button type="button" class="dashboard-btn" data-add-widget="{{ $widgetKey }}">
                            Add
                        </button>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>

    <template id="dashboard-widget-template">
        <article class="dashboard-widget" draggable="true">
            <header class="widget-header">
                <div>
                    <h2 class="widget-title"></h2>
                    <p class="widget-subtitle"></p>
                </div>
                <div class="widget-tools">
                    <button type="button" class="widget-tool" data-remove-widget="" title="Remove from my dashboard"
                        aria-label="Remove widget">
                        ×
                    </button>
                </div>
            </header>
            <div class="widget-body">
                Reload the page after saving to load this widget's current governed data.
            </div>
        </article>
    </template>

    <script>
        (function() {
            'use strict';

            const grid = document.getElementById('dashboard-grid');
            const saveButton = document.getElementById('save-dashboard-layout');
            const saveState = document.getElementById('dashboard-save-state');
            const drawerBackdrop = document.getElementById('widget-drawer-backdrop');
            const openDrawer = document.getElementById('open-widget-drawer');
            const closeDrawer = document.getElementById('close-widget-drawer');
            const emptyState = document.getElementById('empty-dashboard');
            const template = document.getElementById('dashboard-widget-template');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const catalog = @json($catalog);

            let dragged = null;
            let dirty = false;

            function widgetElements() {
                return Array.from(grid.querySelectorAll('.dashboard-widget'));
            }

            function currentLayout() {
                const layout = widgetElements().map(function(widget) {
                    return widget.dataset.widgetKey;
                });

                if (!layout.includes('quick_links')) {
                    return layout;
                }

                return ['quick_links'].concat(layout.filter(function(key) {
                    return key !== 'quick_links';
                }));
            }

            function updateEmptyState() {
                emptyState.hidden = widgetElements().length > 0;
            }

            function updateDrawerButtons() {
                const active = new Set(currentLayout());

                document.querySelectorAll('[data-add-widget]').forEach(function(button) {
                    const key = button.dataset.addWidget;
                    const isActive = active.has(key);
                    button.disabled = isActive;
                    button.textContent = isActive ? 'Added' : 'Add';
                });
            }

            function markDirty() {
                dirty = true;
                saveState.textContent = 'Layout changed — select Save dashboard to keep it.';
                updateEmptyState();
                updateDrawerButtons();
            }

            function bindWidget(widget) {
                const pinnedTop = widget.dataset.pinnedTop === 'true';

                if (!pinnedTop) {
                    widget.addEventListener('dragstart', function() {
                        dragged = widget;
                        widget.classList.add('is-dragging');
                    });

                    widget.addEventListener('dragend', function() {
                        widget.classList.remove('is-dragging');
                        dragged = null;
                        markDirty();
                    });
                }

                const removeButton = widget.querySelector('[data-remove-widget]');
                if (removeButton) {
                    removeButton.addEventListener('click', function() {
                        widget.remove();
                        markDirty();
                    });
                }
            }

            widgetElements().forEach(bindWidget);

            grid.addEventListener('dragover', function(event) {
                event.preventDefault();
                if (!dragged) {
                    return;
                }

                const candidates = widgetElements().filter(function(widget) {
                    return widget !== dragged && widget.dataset.pinnedTop !== 'true';
                });

                const closest = candidates.reduce(function(result, widget) {
                    const box = widget.getBoundingClientRect();
                    const distance = event.clientY - (box.top + box.height / 2);

                    if (distance < 0 && Math.abs(distance) < result.distance) {
                        return {
                            distance: Math.abs(distance),
                            widget: widget,
                        };
                    }

                    return result;
                }, {
                    distance: Number.POSITIVE_INFINITY,
                    widget: null,
                });

                if (closest.widget) {
                    grid.insertBefore(dragged, closest.widget);
                } else {
                    grid.insertBefore(dragged, emptyState);
                }
            });

            openDrawer.addEventListener('click', function() {
                drawerBackdrop.classList.add('is-open');
                drawerBackdrop.setAttribute('aria-hidden', 'false');
                updateDrawerButtons();
            });

            function closeWidgetDrawer() {
                drawerBackdrop.classList.remove('is-open');
                drawerBackdrop.setAttribute('aria-hidden', 'true');
            }

            closeDrawer.addEventListener('click', closeWidgetDrawer);

            drawerBackdrop.addEventListener('click', function(event) {
                if (event.target === drawerBackdrop) {
                    closeWidgetDrawer();
                }
            });

            document.querySelectorAll('[data-add-widget]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const key = button.dataset.addWidget;
                    if (!catalog[key] || currentLayout().includes(key)) {
                        return;
                    }

                    const fragment = template.content.cloneNode(true);
                    const widget = fragment.querySelector('.dashboard-widget');
                    const heading = fragment.querySelector('.widget-title');
                    const subtitle = fragment.querySelector('.widget-subtitle');
                    const removeButton = fragment.querySelector('[data-remove-widget]');

                    widget.dataset.widgetKey = key;
                    widget.dataset.size = catalog[key].size;
                    widget.dataset.pinnedTop = key === 'quick_links' ? 'true' : 'false';
                    widget.draggable = key !== 'quick_links';
                    heading.textContent = catalog[key].title;
                    subtitle.textContent = catalog[key].description;
                    removeButton.dataset.removeWidget = key;
                    removeButton.setAttribute('aria-label', 'Remove ' + catalog[key].title);

                    if (key === 'quick_links') {
                        const firstWidget = widgetElements()[0] || emptyState;
                        grid.insertBefore(widget, firstWidget);
                    } else {
                        grid.insertBefore(widget, emptyState);
                    }
                    bindWidget(widget);
                    markDirty();
                });
            });

            saveButton.addEventListener('click', function() {
                saveButton.disabled = true;
                saveState.textContent = 'Saving dashboard layout…';

                fetch('{{ route('dashboard.layout.save') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            layout: currentLayout(),
                        }),
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Could not save dashboard layout.');
                        }

                        return response.json();
                    })
                    .then(function() {
                        dirty = false;
                        saveState.textContent = 'Dashboard saved. Reloading widget data…';
                        window.location.reload();
                    })
                    .catch(function(error) {
                        saveState.textContent = error.message;
                    })
                    .finally(function() {
                        saveButton.disabled = false;
                    });
            });

            window.addEventListener('beforeunload', function(event) {
                if (!dirty) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            });

            updateEmptyState();
            updateDrawerButtons();
        }());
    </script>
@endsection
