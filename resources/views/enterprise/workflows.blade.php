@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Workflow & Case Management',
        'subtitle' =>
            'One operational view of promotions, transfers, confirmations, corrections, acting appointments and other establishment cases with ownership and escalation visibility.',
    ])
    <div class="enterprise-grid">
        @foreach ($queues as $q)
            <div class="enterprise-card">
                <h3>{{ $q['label'] }}</h3><strong style="font-size:28px">{{ number_format($q['count']) }}</strong>
                <p>Open/active records requiring operational attention.</p>
                @if (Route::has($q['route']))
                    <a class="enterprise-link" href="{{ route($q['route']) }}">Open queue →</a>
                @endif
            </div>
        @endforeach
    </div>
    <div class="enterprise-note">Case decisions remain attributable to the authorised officer and applicable authority.
        High-impact administrative changes use independent review stages and retained evidence.</div>

    <div class="enterprise-section enterprise-card">
        <h2>Promotion governance queue</h2>
        <p class="md-body-sm">Promotion actions now enforce <strong>Prepared → Checked → Recommended → Approved</strong>. The
            same officer cannot perform multiple stages.</p>
        <table class="enterprise-table">
            <tr>
                <th>Employee</th>
                <th>Grade change</th>
                <th>Effective</th>
                <th>Reference</th>
                <th>Status</th>
                <th>Next action</th>
            </tr>
            @forelse($promotions as $p)
                @php
                    $next =
                        ['prepared' => 'checked', 'checked' => 'recommended', 'recommended' => 'approved'][
                            $p->status
                        ] ?? null;
                @endphp
                <tr>
                    <td><strong>{{ $p->employee_name }}</strong><br><small>{{ $p->pay_no ?: 'No pay number' }}</small></td>
                    <td>{{ $p->from_grade_name ?: '—' }} → {{ $p->to_grade_name }}</td>
                    <td>{{ $p->effective_date }}</td>
                    <td>{{ $p->reference_no ?: '—' }}</td>
                    <td>{{ ucfirst($p->status) }}</td>
                    <td>
                        @if ($next)
                            <form method="POST" action="{{ route('promotions.transition', $p->id) }}">@csrf<input
                                    type="hidden" name="transition" value="{{ $next }}"><button
                                    class="md-btn md-btn--filled" type="submit">{{ ucfirst($next) }}</button></form>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No pending promotions.</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="enterprise-section enterprise-card">
        <h2>Prepare promotion</h2>
        <form class="enterprise-form" method="POST" action="{{ route('promotions.store') }}">@csrf
            <select name="employee_id" required>
                <option value="">Employee</option>
                @foreach ($employees as $e)
                    <option value="{{ $e->id }}">{{ $e->name }}{{ $e->pay_no ? ' · ' . $e->pay_no : '' }}
                    </option>
                @endforeach
            </select>
            <select name="from_grade_id">
                <option value="">Current grade / not recorded</option>
                @foreach ($grades as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>
            <select name="to_grade_id" required>
                <option value="">Proposed grade</option>
                @foreach ($grades as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>
            <input type="date" name="effective_date" required>
            <input name="reference_no" placeholder="Authority / reference">
            <textarea class="span2" name="justification" required placeholder="Justification and supporting facts"></textarea>
            <button class="md-btn md-btn--filled" type="submit">Prepare promotion</button>
        </form>
    </div>
@endsection
