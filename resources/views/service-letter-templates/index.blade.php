@extends('layouts.app')
@section('title', 'Service Letter Templates')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Service Letter Templates</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Sinhala and English templates Subject Officers can use when drafting a service letter.
        </p>
    </div>
    <a href="{{ route('service-letter-templates.create') }}" class="md-btn md-btn--filled">+ New Template</a>
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ✓ {{ session('success') }}
</div>
@endif

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th style="text-align:center;">Language</th>
                    <th>Description</th>
                    <th style="text-align:center;">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $t)
                <tr style="{{ !$t->is_active ? 'opacity:.5;' : '' }}">
                    <td class="md-label-md">{{ $t->name }}</td>
                    <td style="text-align:center;">
                        <span class="md-badge md-badge--info">
                            {{ \App\Models\ServiceLetterTemplate::LANGUAGES[$t->language] ?? $t->language }}
                        </span>
                    </td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);max-width:320px;">
                        {{ $t->description ?: '—' }}
                    </td>
                    <td style="text-align:center;">
                        @if($t->is_active)
                            <span class="md-badge md-badge--success">Active</span>
                        @else
                            <span class="md-badge md-badge--neutral">Disabled</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;align-items:center;">
                            <a href="{{ route('service-letter-templates.edit', $t) }}"
                               class="md-btn md-btn--icon" title="Edit">✏️</a>
                            <x-disable-toggle :record="$t" toggle-route="service-letter-templates.toggle"
                                label="template" :require-reason="false" :small="true" />
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="md-table__empty">
                        No templates yet.
                        <a href="{{ route('service-letter-templates.create') }}">Create the first one →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $templates->links('vendor.pagination.material') }}</div>
</div>

@endsection
