{{--
  Reusable disable / re-enable toggle button pair.

  Props:
    $record         — Eloquent model with HasDisableWorkflow
    $toggleRoute    — named route for PATCH toggle (e.g. 'positions.toggle')
    $routeParams    — optional array of route parameters, in order, for
                      nested-resource routes (e.g. 'employee-increments.toggle'
                      needs [$employee, $increment], not just $increment).
                      Defaults to [$record] — every existing single-parameter
                      usage is unaffected.
    $requireReason  — bool, default true
    $label          — human label for confirm dialogs, e.g. 'position'
    $small          — bool, render smaller buttons

  Usage (simple, single-parameter route):
    <x-disable-toggle :record="$pos" toggle-route="positions.toggle" label="position" />

  Usage (nested route needing multiple parameters):
    <x-disable-toggle :record="$increment" toggle-route="employee-increments.toggle"
        :route-params="[$employee, $increment]" label="increment record" />
--}}

@props([
    'record',
    'toggleRoute',
    'routeParams'   => null,
    'requireReason' => true,
    'label'         => 'record',
    'small'         => false,
])

@php
    $btnClass = $small ? 'md-btn md-btn--icon md-btn--sm' : 'md-btn md-btn--icon';
    $uid      = 'dtg_' . $record->getKey() . '_' . str_replace('.', '_', $toggleRoute);
    $params   = $routeParams ?? [$record];
@endphp

@if($record->is_active)
    {{-- Disable button — opens inline reason form ──────────────────────────── --}}
    <button type="button"
            class="{{ $btnClass }}"
            style="color:var(--md-error);"
            title="Disable this {{ $label }}"
            onclick="document.getElementById('{{ $uid }}').style.display='flex';">
        ⊘
    </button>

    {{-- Inline disable form (hidden by default) ───────────────────────────── --}}
    <div id="{{ $uid }}"
         style="display:none;position:fixed;inset:0;z-index:9500;
                background:rgba(0,0,0,.65);align-items:center;justify-content:center;"
         onclick="if(event.target===this)this.style.display='none';">
        <div style="background:var(--md-surface-container-low);
                    border-radius:var(--md-shape-md);
                    padding:24px;max-width:460px;width:100%;
                    box-shadow:var(--md-elevation-3);">
            <div class="md-title-md" style="color:var(--md-error);margin-bottom:8px;">⊘ Disable {{ ucfirst($label) }}</div>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:16px;">
                The {{ $label }} will be hidden from all active lists and reports.
                It is preserved for audit purposes and can be re-enabled at any time.
            </p>
            <form method="POST" action="{{ route($toggleRoute, $params) }}">
                @csrf @method('PATCH')
                @if($requireReason)
                <div class="md-field" style="margin-bottom:14px;">
                    <label class="md-field__label">
                        Reason <span style="color:var(--md-error)">*</span>
                    </label>
                    <textarea name="disable_reason" rows="3" required minlength="10" maxlength="500"
                              class="md-field__input"
                              placeholder="Minimum 10 characters. This is stored in the permanent audit log."></textarea>
                </div>
                @else
                <input type="hidden" name="disable_reason" value="Disabled by {{ auth()->user()->name ?? 'admin' }}">
                @endif
                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="md-btn md-btn--text"
                            onclick="document.getElementById('{{ $uid }}').style.display='none';">
                        Cancel
                    </button>
                    <button type="submit" class="md-btn md-btn--danger">⊘ Disable</button>
                </div>
            </form>
        </div>
    </div>

@else
    {{-- Re-enable button ──────────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route($toggleRoute, $params) }}"
          onsubmit="return confirm('Re-enable {{ addslashes($label) }}? It will appear in all active lists again.');">
        @csrf @method('PATCH')
        <input type="hidden" name="disable_reason" value="">
        <button type="submit" class="{{ $btnClass }}" style="color:var(--md-success);" title="Re-enable">✓</button>
    </form>
@endif
