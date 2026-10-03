@extends('layouts.app')

@section('content')
    <div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="md-label-sm" style="color:var(--md-primary);">ACTION CENTRE</div>
            <h1 class="md-title-lg" style="margin:4px 0 6px;">Requests for Approval</h1>
            <p class="md-body-md" style="margin:0;color:var(--md-on-surface-variant);">
                Approval work currently assigned to your role. Open the relevant module to review the full record before deciding.
            </p>
        </div>
        <div class="md-card" style="padding:12px 16px;min-width:150px;text-align:center;">
            <div class="md-label-sm">Pending</div>
            <div class="md-headline-md" style="color:var(--md-primary);">{{ $totalPending }}</div>
        </div>
    </div>

    @if ($requests->isEmpty())
        <div class="md-card" style="margin-top:20px;padding:28px;text-align:center;">
            <div style="font-size:32px;margin-bottom:8px;">✓</div>
            <div class="md-title-md">No approval requests are waiting</div>
            <div class="md-body-sm" style="margin-top:6px;color:var(--md-on-surface-variant);">
                New requests will appear here when a workflow reaches an approval stage assigned to your role.
            </div>
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-top:20px;">
            @foreach ($requests as $approvalRequest)
                <a href="{{ $approvalRequest['route'] }}" class="md-card"
                    style="padding:18px;text-decoration:none;color:inherit;display:block;border-left:4px solid var(--md-primary);">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                        <div class="md-title-md">{{ $approvalRequest['label'] }}</div>
                        <span class="nav-badge"
                            style="background:var(--md-error);color:var(--md-on-error);font-weight:700;padding:3px 8px;border-radius:999px;min-width:24px;text-align:center;">
                            {{ $approvalRequest['count'] }}
                        </span>
                    </div>
                    <div class="md-body-sm" style="margin-top:8px;color:var(--md-on-surface-variant);">
                        {{ $approvalRequest['description'] }}
                    </div>
                    <div class="md-label-sm" style="margin-top:14px;color:var(--md-primary);">Open approval queue →</div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
