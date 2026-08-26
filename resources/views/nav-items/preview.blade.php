@extends('layouts.app')
@section('title', 'Preview Navigation')
@section('content')

<div style="max-width:480px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('nav-items.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <h2 class="md-headline-sm">Preview Navigation</h2>
    </div>

    <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:16px;">
        Shows exactly what a user with the selected role would see, using the same visibility logic the real
        sidebar uses. Boolean-flag conditions (e.g. "Can view Employee Profiles") are assumed ON for this
        preview — a real account might have them off. Conditions that read live account/system state (like the
        configured entry-verifier role) reflect a freshly-created account of that role, not any specific person.
    </p>

    <form method="GET" style="display:flex;gap:8px;margin-bottom:20px;">
        <select name="role" class="md-field__input" onchange="this.form.submit()">
            @foreach(['super_admin','admin_group','planning_officer','subject_officer'] as $r)
                <option value="{{ $r }}" {{ $role === $r ? 'selected' : '' }}>{{ $r }}</option>
            @endforeach
        </select>
    </form>

    <div class="md-card md-card--elevated" style="padding:8px 0;">
        @forelse($items as $section => $sectionItems)
            <div class="md-label-sm" style="padding:14px 16px 4px;color:var(--md-on-surface-variant);text-transform:uppercase;letter-spacing:1px;">{{ $section }}</div>
            @foreach($sectionItems as $item)
                <div class="md-nav-item" style="cursor:default;">{{ $item->label }}</div>
            @endforeach
        @empty
            <div style="padding:20px;text-align:center;color:var(--md-on-surface-variant);">No visible items for this role.</div>
        @endforelse
    </div>
</div>
@endsection
