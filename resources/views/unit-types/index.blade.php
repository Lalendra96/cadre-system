@extends('layouts.app')
@section('title', 'Unit Types')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <div>
        <h2 class="md-headline-sm">Unit Types</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Classify hospital organisational units (Ward, Department, Clinic, Theatre, etc.)
            and assign units to each type.
        </p>
    </div>
    @if($canSeeInactive)
        <a href="{{ route('unit-types.index', ['show_inactive' => $showInactive ? 0 : 1]) }}"
           class="md-btn {{ $showInactive ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="font-size:12px;">
            {{ $showInactive ? '🔓 Hiding disabled' : '🔓 Show disabled' }}
        </a>
        @endif
        <a href="{{ route('unit-types.create') }}" class="md-btn md-btn--filled">+ New Unit Type</a>
</div>

<form method="GET" style="margin-bottom:14px;">
    <div style="position:relative;max-width:320px;">
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--md-on-surface-variant);">🔍</span>
        <input type="text" name="q" value="{{ request('q') }}"
               class="md-field__input" style="padding-left:36px;height:40px;"
               placeholder="Search name or code…">
    </div>
</form>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th style="width:50px;">Order</th>
                    <th>Type Name</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th style="text-align:center;">Units Assigned</th>
                    <th style="text-align:center;">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($unitTypes as $t)
                <tr style="{{ !$t->is_active ? 'opacity:.5;' : '' }}">
                    <td style="text-align:center;color:var(--md-on-surface-variant);font-size:13px;">{{ $t->sort_order }}</td>
                    <td>
                        <div class="md-label-md">{{ $t->name }}</div>
                    </td>
                    <td>
                        @if($t->code)
                            <code style="font-size:12px;background:var(--md-surface-container-high);
                                         padding:2px 8px;border-radius:4px;color:var(--md-primary);">
                                {{ $t->code }}
                            </code>
                        @else
                            <span style="color:var(--md-on-surface-variant);">—</span>
                        @endif
                    </td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);max-width:240px;">
                        {{ \Illuminate\Support\Str::limit($t->description, 60) ?: '—' }}
                    </td>
                    <td style="text-align:center;">
                        <a href="{{ route('unit-types.edit', $t) }}"
                           class="md-badge md-badge--info" style="text-decoration:none;">
                            {{ $t->units_count }} {{ \Illuminate\Support\Str::plural('unit', $t->units_count) }}
                        </a>
                    </td>
                    <td style="text-align:center;">
                        <span class="md-badge {{ $t->is_active ? 'md-badge--success' : 'md-badge--neutral' }}">
                            {{ $t->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('unit-types.edit', $t) }}" class="md-btn md-btn--icon" title="Edit">✏️</a>
                            <x-disable-toggle :record="$t" toggle-route="unit-types.toggle" label="unit type" :require-reason="false" :small="true" />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="md-table__empty">No unit types yet. <a href="{{ route('unit-types.create') }}">Create one.</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $unitTypes->links('vendor.pagination.material') }}</div>
</div>
@endsection
