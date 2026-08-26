@extends('layouts.app')
@section('title', 'My E-Signature')
@section('content')

<div style="max-width:640px;">
    <h2 class="md-headline-sm" style="margin-bottom:6px;">My E-Signature</h2>
    <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:20px;">
        Used to e-sign Service Letters and Vacancy Availability Letters you approve.
        Your signature image is stored privately and is never publicly accessible —
        only you and Super Admin can view it.
    </p>

    @if(session('success'))
    <div style="background:var(--md-success-container,#1b3a2d);color:var(--md-on-success-container,#9ef0b3);
                padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        ✓ {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div style="background:var(--md-error-container);color:var(--md-on-error-container);
                padding:12px 18px;border-radius:var(--md-shape-sm);margin-bottom:16px;font-size:13px;">
        <ul style="margin:0;padding-left:16px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="md-card md-card--elevated" style="padding:24px;">
        @if($current)
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <span class="md-badge md-badge--success">✓ Signature registered</span>
                <span class="md-body-sm" style="color:var(--md-on-surface-variant);">
                    since {{ $current->created_at->format('d M Y') }}
                </span>
            </div>
            <div style="background:var(--md-surface-container);border-radius:var(--md-shape-sm);
                        padding:16px;margin-bottom:20px;display:flex;justify-content:center;">
                <img src="{{ route('e-signatures.show', $current) }}"
                     alt="Your registered signature"
                     style="max-height:100px;max-width:100%;">
            </div>
            <p class="md-body-sm" style="color:var(--md-on-surface-variant);margin-bottom:16px;">
                Uploading a new signature below will replace this one for all future approvals.
                Documents you've already signed keep their original signature image.
            </p>
        @else
            <div style="text-align:center;padding:20px 0 28px;color:var(--md-on-surface-variant);">
                <div style="font-size:32px;margin-bottom:8px;">✍️</div>
                <p class="md-body-sm">No signature registered yet. Upload one below before you can approve any letter.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('e-signatures.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="md-field" style="margin-bottom:16px;">
                <label class="md-field__label">
                    {{ $current ? 'Replace signature image' : 'Signature image' }}
                    <span style="color:var(--md-error)">*</span>
                </label>
                <input type="file" name="signature" accept=".png,.jpg,.jpeg" required
                       class="md-field__input @error('signature') md-field--error @enderror">
                @error('signature')<div class="md-field__error">{{ $message }}</div>@enderror
                <div class="md-body-sm" style="color:var(--md-on-surface-variant);margin-top:4px;">
                    PNG or JPG, max 512KB. A scanned signature on a transparent or white background works best.
                </div>
            </div>
            <button type="submit" class="md-btn md-btn--filled">
                {{ $current ? 'Replace Signature' : 'Register Signature' }}
            </button>
        </form>
    </div>
</div>

@endsection
