@extends('layouts.app')

@section('title', 'Car Pass Employee Search')

@section('content')
    <div style="margin-bottom:16px;">
        <h2 class="md-headline-sm">Employee Search Result</h2>
        <p class="md-body-sm">Exact {{ $data['search_type'] === 'pay_no' ? 'Pay No' : ucfirst($data['search_type']) }} lookup
            only.</p>
    </div>

    <div class="workforce-panel">
        @forelse ($matches as $employee)
            <div class="md-card"
                style="padding:16px;margin-bottom:12px;display:flex;justify-content:space-between;gap:16px;align-items:center;">
                <div>
                    <strong>{{ $employee->name }}</strong>
                    <div class="md-body-sm">
                        Pay No: {{ $employee->pay_no ?: '—' }} ·
                        Post: {{ $employee->position?->title ?: '—' }} ·
                        Unit: {{ $employee->unit?->name ?: '—' }}
                    </div>
                </div>
                <a class="md-btn md-btn--filled"
                    href="{{ route('car-passes.create', ['employee_id' => $employee->id]) }}">Select Employee</a>
            </div>
        @empty
            <div style="padding:24px;text-align:center;">No active employee matched that exact identifier.</div>
        @endforelse

        <div style="margin-top:12px;">
            <a class="md-btn md-btn--text" href="{{ route('car-passes.create') }}">← Search Again</a>
        </div>
    </div>
@endsection
