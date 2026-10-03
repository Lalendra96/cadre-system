@extends('layouts.app')
@section('title', 'Data Quality Rules')
@section('content')
    <h1 class="md-headline-md">Data Quality Rules</h1>
    <p class="md-body-md" style="color:var(--md-on-surface-variant)">Enable/disable checks and set their severity without
        changing code.</p>
    <section class="workforce-panel" style="margin-top:16px">
        <table class="md-table" style="width:100%">
            <thead>
                <tr>
                    <th>Rule</th>
                    <th>Code</th>
                    <th>Configuration</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rules as $rule)
                    <tr>
                        <td>{{ $rule->label }}</td>
                        <td>
                            <code>{{ $rule->code }}</code>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('data-quality.rules.update', $rule) }}"
                                style="display:flex;gap:10px;align-items:center">
                                @csrf @method('PUT')
                                <select class="md-select" name="severity">
                                    @foreach (['low', 'medium', 'high', 'critical'] as $s)
                                        <option value="{{ $s }}" @selected($rule->severity === $s)>{{ ucfirst($s) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label>
                                    <input type="checkbox" name="is_enabled" value="1" @checked($rule->is_enabled)>
                                    Enabled</label>
                                <button class="md-btn--primary">Save</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
