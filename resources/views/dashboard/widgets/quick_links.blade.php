@php
    $selectedQuickLinks = collect($quickLinks ?? [])->filter(fn($key) => isset($quickLinkCatalog[$key]));
@endphp

<style>
    .quick-link-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 10px;
    }

    .quick-link-card {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 58px;
        padding: 11px 12px;
        border: 1px solid var(--md-outline-variant);
        border-radius: 12px;
        background: var(--md-surface-container-low);
        color: var(--md-on-surface);
        text-decoration: none;
        transition: transform .15s ease, border-color .15s ease, background .15s ease;
    }

    .quick-link-card:hover {
        transform: translateY(-1px);
        border-color: var(--md-primary);
        background: color-mix(in srgb, var(--md-primary) 7%, var(--md-surface-container-low));
    }

    .quick-link-card__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: color-mix(in srgb, var(--md-primary) 14%, transparent);
        color: var(--md-primary);
        font-size: 17px;
        flex: 0 0 auto;
    }

    .quick-link-card strong {
        display: block;
        font-size: 12px;
        line-height: 1.3;
    }

    .quick-link-card small {
        display: block;
        margin-top: 2px;
        color: var(--md-on-surface-variant);
        font-size: 10px;
    }

    .quick-link-config {
        margin-top: 14px;
        border-top: 1px solid var(--md-outline-variant);
        padding-top: 12px;
    }

    .quick-link-config summary {
        cursor: pointer;
        color: var(--md-primary);
        font-size: 12px;
        font-weight: 700;
    }

    .quick-link-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 8px;
        margin-top: 10px;
        max-height: 250px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .quick-link-option {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 9px 10px;
        border: 1px solid var(--md-outline-variant);
        border-radius: 10px;
        background: var(--md-surface-container-lowest);
    }

    .quick-link-option input {
        margin-top: 2px;
    }

    .quick-link-option strong,
    .quick-link-option small {
        display: block;
    }

    .quick-link-option strong {
        font-size: 11px;
    }

    .quick-link-option small {
        margin-top: 2px;
        color: var(--md-on-surface-variant);
        font-size: 10px;
    }
</style>

@if ($selectedQuickLinks->isEmpty())
    <div style="color: var(--md-on-surface-variant); font-size: 12px;">
        No shortcuts selected. Use <strong>Manage Quick Links</strong> below to add authorised destinations.
    </div>
@else
    <div class="quick-link-grid">
        @foreach ($selectedQuickLinks as $key)
            @php
                $link = $quickLinkCatalog[$key];
            @endphp
            <a href="{{ $link['url'] }}" class="quick-link-card"
                @if ($link['open_in_new_tab']) target="_blank"
                    rel="noopener noreferrer" @endif>
                <span class="quick-link-card__icon" aria-hidden="true">↗</span>
                <span>
                    <strong>{{ $link['label'] }}</strong>
                    <small>{{ $link['section'] }}</small>
                </span>
            </a>
        @endforeach
    </div>
@endif

<details class="quick-link-config">
    <summary>Manage Quick Links</summary>

    <form method="POST" action="{{ route('dashboard.quick-links.save') }}">
        @csrf

        <p style="margin: 8px 0 0; color: var(--md-on-surface-variant); font-size: 11px;">
            Choose up to 12 shortcuts. Only navigation destinations already authorised for your account are offered.
        </p>

        <div class="quick-link-options">
            @foreach ($quickLinkCatalog as $key => $link)
                <label class="quick-link-option">
                    <input type="checkbox" name="quick_links[]" value="{{ $key }}" data-quick-link-checkbox
                        @checked(in_array($key, $quickLinks ?? [], true))>
                    <span>
                        <strong>{{ $link['label'] }}</strong>
                        <small>{{ $link['section'] }}</small>
                    </span>
                </label>
            @endforeach
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 10px;">
            <span id="quick-link-count" style="font-size: 11px; color: var(--md-on-surface-variant);"></span>
            <button type="submit" class="dashboard-btn dashboard-btn--primary">
                Save Quick Links
            </button>
        </div>
    </form>
</details>

<script>
    (function() {
        'use strict';

        const boxes = Array.from(document.querySelectorAll('[data-quick-link-checkbox]'));
        const countLabel = document.getElementById('quick-link-count');
        const maximum = 12;

        function refreshQuickLinkCount() {
            const checked = boxes.filter(function(box) {
                return box.checked;
            }).length;

            countLabel.textContent = checked + ' of ' + maximum + ' selected';

            boxes.forEach(function(box) {
                box.disabled = !box.checked && checked >= maximum;
            });
        }

        boxes.forEach(function(box) {
            box.addEventListener('change', refreshQuickLinkCount);
        });

        refreshQuickLinkCount();
    }());
</script>
