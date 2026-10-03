@extends('layouts.app')
@section('content')
    @include('enterprise._head', [
        'title' => 'Regulatory Change Management',
        'subtitle' =>
            'Track a circular, service minute or administrative instruction from receipt through applicability, implementation, independent testing and retained evidence.',
    ])

    <div class="enterprise-section enterprise-card">
        <h2>Register / assess regulatory change</h2>
        <p class="md-body-sm">A change cannot be registered directly as implemented. It must progress through the controlled
            lifecycle and implementation owners cannot independently verify their own work.</p>
        <form class="enterprise-form" method="POST" action="{{ route('governance-control.regulatory.store') }}">@csrf
            <select name="circular_id">
                <option value="">Link uploaded circular / memo (optional)</option>
                @foreach ($circulars as $c)
                    <option value="{{ $c->id }}">{{ $c->title }} · {{ $c->category }}</option>
                @endforeach
            </select>
            <input name="source_type" value="circular" placeholder="Source type" required>
            <input name="source_reference" placeholder="Circular / Service Minute reference" required>
            <input name="title" placeholder="Title" required>
            <input type="date" name="received_on">
            <input type="date" name="target_effective_on">
            <select name="owner_user_id">
                <option value="">Implementation owner</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            <select name="applicable">
                <option value="">Applicability not assessed</option>
                <option value="1">Applicable</option>
                <option value="0">Not applicable</option>
            </select>
            <select name="status" required>
                <option value="received">Received</option>
                <option value="assessing">Assessing</option>
                <option value="planned">Planned</option>
                <option value="not_applicable">Not applicable</option>
            </select>
            <textarea class="span2" name="applicability_assessment" placeholder="Applicability assessment"></textarea>
            <textarea class="span2" name="affected_rules_modules" placeholder="Affected modules, workflows and operational areas"></textarea>
            <div class="span2"><label class="md-label">Affected rule versions</label><select
                    name="affected_rule_version_ids[]" multiple size="5">
                    @foreach ($rules as $rule)
                        <option value="{{ $rule->id }}">{{ $rule->rule_key }} v{{ $rule->version_no }} ·
                            {{ $rule->title }} ({{ $rule->effective_from }})</option>
                    @endforeach
                </select>
            </div>
            <textarea class="span2" name="implementation_tasks_text"
                placeholder="Implementation tasks, one per line. Use [ ] pending and [x] completed.&#10;[ ] Update retirement rule configuration&#10;[ ] Regression test affected workflow"></textarea>
            <textarea name="test_evidence" placeholder="Testing evidence"></textarea>
            <textarea name="implementation_evidence" placeholder="Implementation/configuration evidence"></textarea>
            <button class="md-btn md-btn--filled" type="submit">Register change</button>
        </form>
    </div>

    <div class="enterprise-section enterprise-card">
        <h2>Change register</h2>
        <table class="enterprise-table">
            <tr>
                <th>Change</th>
                <th>Source / linkage</th>
                <th>Owner</th>
                <th>Controlled implementation</th>
                <th>Target</th>
            </tr>
            @forelse($changes as $r)
                @php
                    $selectedRules = json_decode($r->affected_rule_version_ids ?? '[]', true) ?: [];
                    $tasks = json_decode($r->implementation_tasks ?? '[]', true) ?: [];
                    $taskText = collect($tasks)
                        ->map(fn($t) => ($t['completed'] ?? false ? '[x] ' : '[ ] ') . ($t['task'] ?? ''))
                        ->implode("\n");
                @endphp
                <tr>
                    <td><strong>{{ $r->change_no }}</strong><br>{{ $r->title }}<br><small>{{ is_null($r->applicable) ? 'Applicability not assessed' : ($r->applicable ? 'Applicable' : 'Not applicable') }}</small>
                    </td>
                    <td>{{ $r->source_type }} {{ $r->source_reference }}@if ($r->circular_id)
                            <br><small>Linked circular #{{ $r->circular_id }}</small>
                        @endif
                        <br>
                        <small>{{ count($selectedRules) }} linked rule version(s)</small>
                    </td>
                    <td>{{ $r->owner_name ?: 'Unassigned' }}</td>
                    <td>
                        <form method="POST" action="{{ route('governance-control.regulatory.update', $r->id) }}">@csrf
                            <select name="status">
                                @foreach (['received' => 'Received', 'assessing' => 'Assessing', 'planned' => 'Planned', 'implementing' => 'Implementing', 'testing' => 'Testing', 'implemented' => 'Implemented', 'not_applicable' => 'Not applicable', 'closed' => 'Closed'] as $value => $label)
                                    <option value="{{ $value }}" @selected($r->status === $value)>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="applicable">
                                <option value="">Unassessed</option>
                                <option value="1" @selected($r->applicable === true || $r->applicable === 1)>Applicable</option>
                                <option value="0" @selected($r->applicable === false || $r->applicable === 0)>Not applicable</option>
                            </select>
                            <input type="date" name="target_effective_on" value="{{ $r->target_effective_on }}">
                            <select name="owner_user_id">
                                <option value="">Implementation owner</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" @selected((int) $r->owner_user_id === (int) $u->id)>{{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="circular_id">
                                <option value="">No circular linkage</option>
                                @foreach ($circulars as $c)
                                    <option value="{{ $c->id }}" @selected((int) $r->circular_id === (int) $c->id)>{{ $c->title }}
                                    </option>
                                @endforeach
                            </select>
                            <textarea name="applicability_assessment" placeholder="Applicability assessment">{{ $r->applicability_assessment }}</textarea>
                            <textarea name="affected_rules_modules" placeholder="Affected rules/modules">{{ $r->affected_rules_modules }}</textarea>
                            <label class="md-label">Affected rule versions</label>
                            <select name="affected_rule_version_ids[]" multiple size="4">
                                @foreach ($rules as $rule)
                                    <option value="{{ $rule->id }}" @selected(in_array($rule->id, $selectedRules))>{{ $rule->rule_key }}
                                        v{{ $rule->version_no }}</option>
                                @endforeach
                            </select>
                            <textarea name="implementation_tasks_text" rows="5" placeholder="[ ] task / [x] completed task">{{ $taskText }}</textarea>
                            <textarea name="test_evidence" placeholder="Testing evidence">{{ $r->test_evidence }}</textarea>
                            <textarea name="implementation_evidence" placeholder="Implementation evidence">{{ $r->implementation_evidence }}</textarea>
                            <button class="md-btn md-btn--filled" type="submit">Update stage</button>
                        </form>
                    </td>
                    <td>{{ $r->target_effective_on ?: '—' }}@if ($r->verified_on)
                            <br><small>Verified {{ $r->verified_on }}</small>
                        @endif
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="5">No regulatory changes yet.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    @endsection
