@extends('layouts.app')
@section('title', 'Letter Detail')
@push('head')
<style>
.lr-split {
  display: grid; grid-template-columns: 1fr 380px; gap: 0;
  height: calc(100vh - 64px - 48px); overflow: hidden;
}
.lr-viewer { display:flex; flex-direction:column; background:var(--md-surface-container-lowest); border-right:1px solid var(--md-outline-variant); overflow:hidden; }
.lr-viewer__tabs { display:flex; gap:2px; padding:8px 12px; background:var(--md-surface-container-high); border-bottom:1px solid var(--md-outline-variant); overflow-x:auto; flex-shrink:0; }
.lr-viewer__tab { display:flex; align-items:center; gap:8px; padding:6px 14px; border-radius:var(--md-shape-sm); font-size:13px; color:var(--md-on-surface-variant); cursor:pointer; border:1px solid transparent; background:transparent; white-space:nowrap; max-width:220px; overflow:hidden; text-overflow:ellipsis; transition:background .12s ease; }
.lr-viewer__tab:hover { background:color-mix(in srgb, var(--md-on-surface) 8%, transparent); }
.lr-viewer__tab--active { background:var(--md-secondary-container); color:var(--md-on-secondary-container); }
.lr-viewer__frame-wrap { flex:1; display:flex; align-items:stretch; overflow:hidden; }
.lr-viewer__frame { width:100%; height:100%; border:none; display:none; }
.lr-viewer__frame--active { display:block; }
.lr-viewer__image-wrap { flex:1; overflow:auto; display:none; align-items:flex-start; justify-content:center; padding:16px; }
.lr-viewer__image-wrap--active { display:flex; }
.lr-viewer__image { max-width:100%; height:auto; border-radius:var(--md-shape-sm); box-shadow:var(--md-elevation-3); }
.lr-viewer__unavail { flex:1; display:none; align-items:center; justify-content:center; flex-direction:column; gap:16px; color:var(--md-on-surface-variant); }
.lr-viewer__unavail--active { display:flex; }
.lr-panel { display:flex; flex-direction:column; overflow-y:auto; background:var(--md-surface-container-low); }
.lr-panel__section { padding:16px 20px; border-bottom:1px solid var(--md-outline-variant); }
.lr-panel__label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.8px; color:var(--md-on-surface-variant); margin-bottom:10px; }
.lr-status-row { display:flex; align-items:center; gap:10px; padding:6px 0; font-size:13px; }
.lr-status-row__name { flex:1; }
@media (max-width:900px) { .lr-split { grid-template-columns:1fr; height:auto; overflow:visible; } .lr-viewer { height:60vh; } }
</style>
@endpush

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
    <div>
        <h2 class="md-headline-sm" style="margin-bottom:2px;">{{ $letter->title }}</h2>
        <p class="md-body-sm" style="color:var(--md-on-surface-variant);">
            Shared on {{ $letter->created_at->format('d M Y, h:i A') }}
            @if($letter->description) · {{ $letter->description }} @endif
        </p>
    </div>
    <a href="{{ route('letters.index') }}" class="md-btn md-btn--text">← My Letters</a>
</div>

<div class="lr-split">
    {{-- LEFT: FILE VIEWER --}}
    <div class="lr-viewer">
        <div class="lr-viewer__tabs" id="attachmentTabs">
            @forelse($letter->attachments as $i => $a)
                <button class="lr-viewer__tab {{ $i===0 ? 'lr-viewer__tab--active' : '' }}" onclick="switchAttachment({{ $i }})" title="{{ $a->original_filename }}">
                    &#128196; {{ \Illuminate\Support\Str::limit($a->original_filename, 24) }}
                    <span style="font-size:10px;opacity:.7;">{{ $a->human_size }}</span>
                </button>
            @empty
                <span class="md-body-sm" style="padding:6px 12px;color:var(--md-on-surface-variant);">No files.</span>
            @endforelse
        </div>
        <div class="lr-viewer__frame-wrap">
            @forelse($letter->attachments as $i => $a)
                @php($ext = strtolower(pathinfo($a->original_filename, PATHINFO_EXTENSION)))
                @if($a->isAvailable())
                    @if($ext === 'pdf')
                        <div id="frame-{{ $i }}" class="lr-viewer__unavail {{ $i===0 ? 'lr-viewer__unavail--active' : '' }}" style="flex-direction:column;align-items:stretch;">
                        <object data="{{ route('letters.attachments.preview', [$letter, $a]) }}"
                                type="application/pdf"
                                style="width:100%;height:100%;border:none;"
                                onload="this.parentElement.classList.add('pdf-loaded')">
                            <embed src="{{ route('letters.attachments.preview', [$letter, $a]) }}"
                                   type="application/pdf"
                                   style="width:100%;height:100%;border:none;">
                                <div style="padding:40px;text-align:center;color:var(--md-on-surface-variant);">
                                    <p>PDF preview unavailable — <a href="{{ route('letters.attachments.download', [$letter, $a]) }}" class="md-btn md-btn--tonal" style="display:inline-flex;margin-top:10px;">⬇ Download</a></p>
                                </div>
                            </embed>
                        </object>
                    </div>
                    @elseif(in_array($ext, ['jpg','jpeg','png']))
                        <div id="frame-{{ $i }}" class="lr-viewer__image-wrap {{ $i===0 ? 'lr-viewer__image-wrap--active' : '' }}">
                            <img class="lr-viewer__image" src="{{ route('letters.attachments.preview', [$letter, $a]) }}" alt="{{ $a->original_filename }}">
                        </div>
                    @else
                        <div id="frame-{{ $i }}" class="lr-viewer__unavail {{ $i===0 ? 'lr-viewer__unavail--active' : '' }}">
                            <div style="font-size:3rem;">&#128462;</div>
                            <p class="md-title-sm">{{ $a->original_filename }}</p>
                            <a href="{{ route('letters.attachments.download', [$letter, $a]) }}" class="md-btn md-btn--tonal">&#11015; Download</a>
                        </div>
                    @endif
                @else
                    <div id="frame-{{ $i }}" class="lr-viewer__unavail {{ $i===0 ? 'lr-viewer__unavail--active' : '' }}">
                        <div style="font-size:3rem;">&#128683;</div>
                        <p class="md-body-sm" style="color:var(--md-on-surface-variant);text-align:center;">Removed {{ optional($a->file_removed_at)->format('d M Y') }}</p>
                    </div>
                @endif
            @empty
                <div class="lr-viewer__unavail lr-viewer__unavail--active">
                    <p class="md-body-sm" style="color:var(--md-on-surface-variant);">No files attached.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- RIGHT: STATUS PANEL --}}
    <div class="lr-panel">
        <div class="lr-panel__section">
            <div class="lr-panel__label">Files</div>
            @foreach($letter->attachments as $a)
                <div style="display:flex;align-items:center;gap:10px;padding:4px 0;">
                    <span>&#128196;</span>
                    <div style="flex:1;min-width:0;"><div class="md-label-md" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $a->original_filename }}</div><div class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $a->human_size }}</div></div>
                    @if($a->isAvailable())
                        <a href="{{ route('letters.attachments.download', [$letter, $a]) }}" class="md-btn md-btn--outlined md-btn--sm">&#11015;</a>
                    @else
                        <span class="md-badge md-badge--neutral" style="font-size:10px;">Removed</span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="lr-panel__section">
            <div class="lr-panel__label">Review Status</div>
            @foreach($letter->recipients as $r)
                <div class="lr-status-row">
                    <div class="lr-status-row__name">
                        <div class="md-label-md">{{ $r->user->name }}</div>
                        <div class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $r->user->category->name ?? '—' }}</div>
                    </div>
                    <span class="md-badge {{ $r->is_read ? 'md-badge--success' : 'md-badge--neutral' }}">{{ $r->is_read ? 'Read' : 'Unread' }}</span>
                    @php($sb = ['pending'=>'neutral','reviewed'=>'info','approved'=>'success','rejected'=>'critical'][$r->status] ?? 'neutral')
                    <span class="md-badge md-badge--{{ $sb }}">{{ ucfirst($r->status) }}</span>
                </div>
                @if($r->remarks)
                    <p class="md-body-sm" style="color:var(--md-on-surface-variant);padding:2px 4px 10px 4px;">{{ $r->remarks }}</p>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const total = {{ $letter->attachments->count() }};
    const frameTypes = {
        @foreach($letter->attachments as $i => $a)
            @php($ext = strtolower(pathinfo($a->original_filename, PATHINFO_EXTENSION)))
            {{ $i }}: '{{ in_array($ext, ['jpg','jpeg','png']) ? "image" : ($ext === "pdf" ? "pdf" : "other") }}',
        @endforeach
    };

    window.switchAttachment = function (index) {
        for (let i = 0; i < total; i++) {
            const el = document.getElementById('frame-' + i);
            if (el) el.classList.remove('lr-viewer__frame--active','lr-viewer__image-wrap--active','lr-viewer__unavail--active');
            const tab = document.querySelectorAll('.lr-viewer__tab')[i];
            if (tab) tab.classList.remove('lr-viewer__tab--active');
        }
        const active = document.getElementById('frame-' + index);
        const type = frameTypes[index];
        if (active) {
            if (type === 'pdf')   active.classList.add('lr-viewer__frame--active');
            if (type === 'image') active.classList.add('lr-viewer__image-wrap--active');
            if (type === 'other' || type === undefined) active.classList.add('lr-viewer__unavail--active');
        }
        const tab = document.querySelectorAll('.lr-viewer__tab')[index];
        if (tab) tab.classList.add('lr-viewer__tab--active');
    };
})();
</script>
@endpush
