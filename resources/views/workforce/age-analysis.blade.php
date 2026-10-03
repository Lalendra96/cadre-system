@extends('layouts.app')
@section('title', 'Employee Age Analysis')
@section('content')
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Workforce planning</div>
            <h1 class="page-title">Employee Age Analysis</h1>
            <p class="page-subtitle">Aggregated age bands and positions with higher retirement exposure. Names are not
                displayed.</p>
        </div>
    </div>
    <section class="md-card" style="padding:20px;margin-bottom:16px">
        <h2 class="md-title-md">Age bands</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-top:12px">
            @foreach ($bands as $band => $count)
                <div class="md-card" style="padding:14px">
                    <div class="md-body-sm">{{ ucwords(str_replace('_', ' ', $band)) }}</div>
                    <strong class="md-headline-sm">{{ $count }}</strong>
                </div>
            @endforeach
        </div>
    </section>
    <section class="md-card" style="padding:20px">
        <h2 class="md-title-md">Retirement exposure by position</h2>
        <table class="md-table" style="width:100%;margin-top:12px">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Employees aged 55+</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byPosition as $position => $count)
                    <tr>
                        <td>{{ $position }}</td>
                        <td>{{ $count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No age data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
