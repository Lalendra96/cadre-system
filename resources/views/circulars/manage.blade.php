@extends('layouts.app')
@section('title', 'Manage Circulars')
@section('content')
@php($user = auth()->user())
@php($canUpload = $user->isSubjectOfficer() || $user->isSuperAdmin() || $user->isAdminGroup())

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 class="md-headline-sm">Manage Circulars</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Upload and revoke memos/circulars here. The public browsable portal (read-only, no login) is at
            <a href="{{ route('circulars.index') }}" target="_blank" style="color:var(--md-primary);">{{ url('/circulars') }} ↗</a>.
        </p>
    </div>
    @if($canUpload)
        <a href="{{ route('circulars.create') }}" class="md-btn md-btn--filled">+ Upload</a>
    @endif
</div>

@if(session('success'))
<div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
            padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
    ✓ {{ session('success') }}
</div>
@endif

@if(session('new_circular_id'))
    @php($newCircular = $circulars->firstWhere('id', session('new_circular_id')))
    @if($newCircular)
    <div class="md-card md-card--elevated" style="padding:16px 20px;margin-bottom:16px;background:var(--md-surface-container);">
        <div class="md-label-md" style="margin-bottom:8px;">🔗 Public Share Link</div>
        <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" readonly id="newShareLink"
                   value="{{ route('circulars.public-show', $newCircular->share_token) }}"
                   class="md-field__input" style="font-size:12px;">
            <button type="button" class="md-btn md-btn--outlined" onclick="copyLink('newShareLink', this)">Copy</button>
        </div>
    </div>
    @endif
@endif

<form method="GET" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search title or description…"
           class="md-field__input" style="max-width:280px;">
    <select name="category" class="md-field__input" style="max-width:200px;" onchange="this.form.submit()">
        <option value="">All categories</option>
        @foreach(\App\Models\Circular::CATEGORY_LABELS as $key => $label)
            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="md-btn md-btn--outlined">Search</button>
</form>

<div class="md-card md-card--elevated">
    <div style="overflow-x:auto;">
        <table class="md-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th style="text-align:center;">Category</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th style="text-align:right;">Views</th>
                    <th>Share Link</th>
                    @if($canUpload)<th>Actions</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($circulars as $c)
                <tr>
                    <td class="md-label-md">{{ $c->title }}</td>
                    <td style="text-align:center;">
                        <span class="md-badge md-badge--info">{{ \App\Models\Circular::CATEGORY_LABELS[$c->category] ?? $c->category }}</span>
                    </td>
                    <td class="md-body-sm">{{ $c->uploadedBy->name ?? '—' }}</td>
                    <td class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $c->created_at->format('d M Y') }}</td>
                    <td style="text-align:right;" class="md-body-sm">{{ number_format($c->view_count) }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <input type="text" readonly id="link-{{ $c->id }}"
                                   value="{{ route('circulars.public-show', $c->share_token) }}"
                                   class="md-field__input" style="font-size:11px;width:180px;" onclick="this.select()">
                            <button type="button" class="md-btn md-btn--icon" title="Copy link" onclick="copyLink('link-{{ $c->id }}', this)">📋</button>
                        </div>
                    </td>
                    @if($canUpload)
                    <td>
                        @if($user->isSuperAdmin() || $c->uploaded_by === $user->id)
                        <div style="display:flex;gap:4px;align-items:center;">
                            @if($user->canManageCircularGroups())
                                <a href="{{ route('circulars.send-form', $c) }}" class="md-btn md-btn--icon" title="Send to Groups">📧</a>
                            @endif
                            <form method="POST" action="{{ route('circulars.toggle', $c) }}" style="display:inline;"
                                  onsubmit="return confirm('Revoke this circular? The public link will stop working immediately.');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="md-btn md-btn--icon" title="Revoke">🚫</button>
                            </form>
                        </div>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canUpload ? 7 : 6 }}" class="md-table__empty">
                        No circulars yet.
                        @if($canUpload)<a href="{{ route('circulars.create') }}">Upload the first one →</a>@endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="md-card__footer">{{ $circulars->links('vendor.pagination.material') }}</div>
</div>
@endsection

@push('scripts')
<script>
function copyLink(inputId, btn) {
    var input = document.getElementById(inputId);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function () {
        var original = btn.textContent;
        btn.textContent = '✓';
        setTimeout(function () { btn.textContent = original; }, 1500);
    }).catch(function () {
        document.execCommand('copy');
    });
}
</script>
@endpush
