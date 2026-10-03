<div class="role-dashboard__header">
    <div class="role-dashboard__header-copy">
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </div>
    <div class="role-dashboard__identity">
        <div>
            <strong>{{ $asAt->format('l, d F Y') }}</strong>
            <span>System snapshot as at {{ $asAt->format('H:i') }}</span>
        </div>
        <img src="{{ asset('images/branding/hospital-logo.png') }}" alt="Hospital logo">
    </div>
</div>
