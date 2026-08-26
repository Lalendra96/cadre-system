@extends('layouts.app')
@section('title', 'User Categories')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
        <h2 class="md-headline-sm">User Categories</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:2px;">
            Admin Group sub-roles. The <strong>Can Receive Letters</strong> flag controls which categories
            appear in the Letter Sharing recipient picker — no code change required.
        </p>
    </div>
    @if($canSeeInactive)
        <a href="{{ route('categories.index', ['show_inactive' => $showInactive ? 0 : 1]) }}"
           class="md-btn {{ $showInactive ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="font-size:12px;">
            {{ $showInactive ? '🔓 Hiding disabled' : '🔓 Show disabled' }}
        </a>
        @endif
        <a href="{{ route('categories.create') }}" class="md-btn md-btn--filled">+ New Category</a>
</div>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th style="width:50px;">Order</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th style="text-align:center;">Can Receive Letters</th>
                    <th style="text-align:center;">Users</th>
                    <th style="text-align:center;">Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $c)
                <tr style="{{ !$c->is_active ? 'opacity:.5;' : '' }}">
                    <td style="color:var(--md-on-surface-variant);font-size:13px;text-align:center;">{{ $c->sort_order }}</td>
                    <td>
                        <div class="md-label-md">{{ $c->name }}</div>
                    </td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $c->description ?: '—' }}</td>
                    <td style="text-align:center;">
                        @if($c->can_receive_letters)
                            <span class="md-badge md-badge--success">✓ Yes</span>
                        @else
                            <span class="md-badge md-badge--neutral">No</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @php($userCount = $c->users()->count())
                        <a href="{{ route('categories.edit', $c) }}"
                           class="md-badge md-badge--info"
                           title="Click to see who is assigned"
                           style="text-decoration:none;">
                            {{ $userCount }} {{ \Illuminate\Support\Str::plural('officer', $userCount) }}
                        </a>
                    </td>
                    <td style="text-align:center;">
                        @if($c->is_active)
                            <span class="md-badge md-badge--success">Active</span>
                        @else
                            <span class="md-badge md-badge--neutral">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('categories.edit', $c) }}" class="md-btn md-btn--icon" title="Edit">&#9998;</a>
                            <x-disable-toggle :record="$c" toggle-route="categories.toggle" label="category" :require-reason="false" :small="true" />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="md-table__empty">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $categories->links('vendor.pagination.material') }}</div>
</div>
@endsection
