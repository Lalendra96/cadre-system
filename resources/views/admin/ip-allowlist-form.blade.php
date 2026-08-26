@extends('layouts.app')
@section('title', 'Add IP Range')
@section('content')
<h2 class="md-headline-sm" style="margin-bottom:16px;">Add IP Allowlist Entry</h2>
<div class="md-card md-card--elevated" style="max-width:500px;">
    <form method="POST" action="{{ route('ip-allowlist.store') }}">
        @csrf
        <div class="md-card__body" style="display:flex;flex-direction:column;gap:16px;">
            @if($errors->any())
                <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px;border-radius:var(--md-shape-sm);font-size:13px;">
                    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                </div>
            @endif
            <div class="md-field">
                <label class="md-field__label">CIDR Range *</label>
                <input type="text" name="cidr" class="md-field__input" value="{{ old('cidr') }}" placeholder="e.g. 192.168.1.0/24 or 10.0.0.5" required>
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">Use /32 for a single IP. IPv4 and IPv6 supported.</div>
            </div>
            <div class="md-field">
                <label class="md-field__label">Description</label>
                <input type="text" name="description" class="md-field__input" value="{{ old('description') }}" placeholder="e.g. Hospital LAN">
            </div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_active" value="1" checked>
                <span class="md-label-lg">Active immediately</span>
            </label>
        </div>
        <div class="md-card__footer">
            <a href="{{ route('ip-allowlist.index') }}" class="md-btn md-btn--text">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">Add Range</button>
        </div>
    </form>
</div>
@endsection
