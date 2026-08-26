@extends('layouts.app')
@section('title', 'No Access')
@section('content')
<div class="md-flex-center" style="min-height:60vh;flex-direction:column;gap:16px;">
    <div class="md-card" style="max-width:480px;width:100%;">
        <div class="md-card__body md-text-center" style="padding:40px 24px;">
            <div style="font-size:3rem;margin-bottom:16px;">&#128274;</div>
            <h2 class="md-h3" style="margin-bottom:8px;">No sections available</h2>
            <p class="md-subtitle" style="margin-bottom:24px;">
                Your account does not currently have access to any sections.<br>
                Contact the system administrator to have your permissions updated.
            </p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="md-btn md-btn--outlined">Sign out</button>
            </form>
        </div>
    </div>
</div>
@endsection
