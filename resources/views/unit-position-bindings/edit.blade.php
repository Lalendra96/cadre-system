@extends('layouts.app')
@section('title', 'Manage Positions — ' . $unit->name)
@section('content')

<div style="max-width:640px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('unit-position-bindings.index') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Manage Positions</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $unit->name }}</p>
        </div>
    </div>

    @if(session('success'))
    <div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
    @endif

    <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:14px;">
        Tick every position that's relevant to this unit. Leaving everything unticked reverts to showing all
        positions on the entry screen — it does not hide the unit itself or any data already entered.
    </p>

    <form method="POST" action="{{ route('unit-position-bindings.update', $unit) }}" class="md-card md-card--elevated">
        @csrf
        @method('PUT')

        <div class="md-card__body">
            <div style="display:flex;gap:10px;margin-bottom:12px;">
                <button type="button" class="md-btn md-btn--outlined" style="font-size:11px;" onclick="document.querySelectorAll('.pos-check').forEach(c=>c.checked=true)">Select All</button>
                <button type="button" class="md-btn md-btn--outlined" style="font-size:11px;" onclick="document.querySelectorAll('.pos-check').forEach(c=>c.checked=false)">Clear All</button>
                <input type="text" id="posFilter" placeholder="Filter positions…" class="md-field__input" style="max-width:220px;margin-left:auto;" oninput="filterPositions(this.value)">
            </div>
            <div style="max-height:420px;overflow-y:auto;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);padding:12px;">
                @foreach($positions as $p)
                <label class="pos-row" data-title="{{ strtolower($p->title) }}" style="display:flex;align-items:center;gap:8px;padding:6px 4px;cursor:pointer;font-size:13px;">
                    <input type="checkbox" name="position_ids[]" class="pos-check" value="{{ $p->id }}"
                           {{ in_array($p->id, old('position_ids', $boundIds)) ? 'checked' : '' }}>
                    {{ $p->title }}
                </label>
                @endforeach
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="{{ route('unit-position-bindings.index') }}" class="md-btn md-btn--outlined">Cancel</a>
            <button type="submit" class="md-btn md-btn--filled">Save</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function filterPositions(term) {
    term = term.toLowerCase();
    document.querySelectorAll('.pos-row').forEach(function (row) {
        row.style.display = row.dataset.title.includes(term) ? 'flex' : 'none';
    });
}
</script>
@endpush
