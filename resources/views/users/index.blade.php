@extends('layouts.app')
@section('title', 'Users')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <h2 class="md-headline-sm">Users</h2>
    <div style="display:flex;gap:8px;align-items:center;">
        @if($canSeeInactive)
        <a href="{{ route('users.index', array_merge(request()->query(), ['show_inactive' => $showInactive ? 0 : 1])) }}"
           class="md-btn {{ $showInactive ? 'md-btn--tonal' : 'md-btn--outlined' }}"
           style="font-size:12px;">
            {{ $showInactive ? '🔓 Hiding disabled' : '🔓 Show disabled accounts' }}
        </a>
        @endif
        <a href="{{ route('users.create') }}" class="md-btn md-btn--filled">+ New User</a>
    </div>
</div>

{{-- Search & filter bar --}}
<form method="GET" action="{{ route('users.index') }}"
      style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
    <div style="position:relative;flex:1;min-width:220px;max-width:360px;">
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--md-on-surface-variant);pointer-events:none;">&#128269;</span>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or email…"
               class="md-field__input" style="padding-left:36px;height:40px;">
    </div>
    <select name="role" class="md-field__input" style="height:40px;max-width:200px;" onchange="this.form.submit()">
        <option value="">All roles</option>
        @foreach(\App\Models\User::ROLE_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('role') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" class="md-field__input" style="height:40px;max-width:160px;" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending reset</option>
        <option value="requested" {{ request('status') === 'requested' ? 'selected' : '' }}>Reset requested</option>
    </select>
    <button type="submit" class="md-btn md-btn--tonal" style="height:40px;">Filter</button>
    @if(request()->hasAny(['q','role','status']))
        <a href="{{ route('users.index') }}" class="md-btn md-btn--text" style="height:40px;">Clear</a>
    @endif
</form>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role(s)</th>
                    <th>Category</th>
                    <th>Subject Codes</th>
                    <th>Status</th>
                    <th style="min-width:160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                <tr>
                    <td>
                        <div class="md-label-md">{{ $u->name }}</div>
                        @if($u->force_password_change)
                            <span class="md-badge md-badge--warning" style="font-size:10px;margin-top:2px;">&#9888; Temp password</span>
                        @endif
                        @if($u->password_reset_requested_at)
                            <span class="md-badge md-badge--critical" style="font-size:10px;margin-top:2px;"
                                  title="Requested {{ $u->password_reset_requested_at->diffForHumans() }}">
                                &#128274; Reset requested
                            </span>
                        @endif
                    </td>
                    <td class="md-body-sm">{{ $u->email }}</td>
                    <td>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                            @foreach($u->userRoles as $r)
                                <span class="md-badge md-badge--info">{{ \App\Models\User::ROLE_LABELS[$r->role] ?? $r->role }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td>{{ $u->category->name ?? '—' }}</td>
                    <td>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                            @forelse($u->subjectCodes as $sc)
                                <span class="md-badge md-badge--neutral" title="{{ $sc->name }}">{{ $sc->code }}</span>
                            @empty
                                <span style="color:var(--md-on-surface-variant);font-size:12px;">—</span>
                            @endforelse
                        </div>
                    </td>
                    <td>
                        @if($u->is_active)
                            <span class="md-badge md-badge--success">Active</span>
                        @else
                            <span class="md-badge md-badge--neutral">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;align-items:center;">
                            <a href="{{ route('users.edit', $u) }}" class="md-btn md-btn--icon" title="Edit user">&#9998;</a>
                            <a href="{{ route('users.reset-password', $u) }}"
                               class="md-btn md-btn--icon"
                               style="{{ $u->force_password_change ? 'color:var(--md-warning);' : '' }}"
                               title="{{ $u->force_password_change ? 'Pending temp password reset' : 'Reset password' }}">&#128274;</a>
                            @if($u->is_active && $u->id !== auth()->id())
                            <x-disable-toggle :record="$u" toggle-route="users.toggle" label="user account" :require-reason="true" :small="true" />
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="md-table__empty">
                        @if(request()->hasAny(['q','role','status']))
                            No users match your search. <a href="{{ route('users.index') }}">Clear filters</a>
                        @else
                            No users found.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer" style="justify-content:space-between;">
        <span class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $users->total() }} user(s)</span>
        {{ $users->links('vendor.pagination.material') }}
    </div>
</div>
@endsection
