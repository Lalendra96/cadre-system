@extends('layouts.app')
@section('title', 'Send — ' . $circular->title)
@section('content')

<div style="max-width:640px;margin:0 auto;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('circulars.manage') }}" class="md-btn md-btn--icon">&#8592;</a>
        <div>
            <h2 class="md-headline-sm">Send to Groups</h2>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);">{{ $circular->title }}</p>
        </div>
    </div>

    @if(session('success'))
    <div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">✓ {{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    @if($groups->isEmpty())
    <div class="md-card md-card--elevated" style="padding:32px;text-align:center;color:var(--md-on-surface-variant);">
        No position groups have been configured yet. Ask your Super Admin to set one up
        (e.g. "Nursing Staff") under <strong>Position Groups</strong>.
    </div>
    @else
    <form method="POST" action="{{ route('circulars.send', $circular) }}" class="md-card md-card--elevated"
          onsubmit="return confirm('Send this circular by email to every employee in the selected group(s) under your subject codes? This cannot be undone.');">
        @csrf

        <div class="md-card__body">
            <label class="md-field__label" style="display:block;margin-bottom:10px;">
                Select group(s) to send to <span style="color:var(--md-error)">*</span>
            </label>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:14px;">
                Only employees under <strong>your own assigned subject codes</strong> will receive this —
                selecting a group never reaches employees outside your subject codes, regardless of who else is in that group hospital-wide.
            </p>
            <div style="display:flex;flex-direction:column;gap:4px;">
                @foreach($groups as $g)
                <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--md-shape-sm);
                              cursor:pointer;background:var(--md-surface-container);">
                    <input type="checkbox" name="position_group_ids[]" value="{{ $g->id }}">
                    <div>
                        <div class="md-label-md" style="font-size:13px;">{{ $g->name }}</div>
                        @if($g->description)
                            <div class="md-body-sm" style="color:var(--md-on-surface-variant);font-size:11.5px;">{{ $g->description }}</div>
                        @endif
                    </div>
                    <span class="md-badge md-badge--info" style="margin-left:auto;">{{ $g->positions_count }} position(s)</span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="md-card__footer" style="display:flex;justify-content:flex-end;">
            <button type="submit" class="md-btn md-btn--filled">📧 Send Now</button>
        </div>
    </form>
    @endif

    @if($recentSends->isNotEmpty())
    <div class="md-card md-card--elevated" style="margin-top:20px;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--md-outline-variant);">
            <span class="md-label-md">Recent Sends</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="md-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Sent By</th>
                        <th style="text-align:right;">Recipients</th>
                        <th style="text-align:right;">Sent</th>
                        <th style="text-align:right;">Failed</th>
                        <th style="text-align:right;">No Email</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentSends as $s)
                    <tr>
                        <td class="md-body-sm">{{ $s->created_at->format('d M Y H:i') }}</td>
                        <td class="md-body-sm">{{ $s->sentBy->name ?? '—' }}</td>
                        <td style="text-align:right;">{{ $s->recipient_count }}</td>
                        <td style="text-align:right;color:var(--md-success,#2e7d32);">{{ $s->sent_count }}</td>
                        <td style="text-align:right;color:{{ $s->failed_count > 0 ? 'var(--md-error)' : 'var(--md-on-surface-variant)' }};">{{ $s->failed_count }}</td>
                        <td style="text-align:right;color:var(--md-on-surface-variant);">{{ $s->skipped_no_email_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
