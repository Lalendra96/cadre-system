@extends('layouts.app')
@section('title', 'Bulk Employee Correction')
@section('content')
    <h1 class="md-headline-md">Bulk Employee Correction</h1>
    <p class="md-body-md">Authorized correction tool. No records are deleted; every changed employee is written to the audit
        log.</p>
    <form method="POST" action="{{ route('employees.bulk-correction.update') }}">
        @csrf @method('PUT')
        <div class="workforce-panel" style="margin-bottom:16px">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px">
                <label>Field<select class="md-select" name="field" id="bulkField" required onchange="toggleBulkInputs()">
                        <option value="">Choose…</option>
                        <option value="unit_id">Unit</option>
                        <option value="subject_code_id">Subject Code</option>
                        <option value="employment_status">Employment Status</option>
                    </select>
                </label>
                <label id="bulk-unit" style="display:none">New Unit<select class="md-select" name="unit_id">
                        <option value="">Choose…</option>
                        @foreach ($units as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label id="bulk-subject_code" style="display:none">New Subject Code<select class="md-select"
                        name="subject_code_id">
                        <option value="">Choose…</option>
                        @foreach ($subjectCodes as $s)
                            <option value="{{ $s->id }}">{{ $s->code }}</option>
                        @endforeach
                    </select>
                </label>
                <label id="bulk-employment_status" style="display:none">New Status<select class="md-select"
                        name="employment_status">
                        @foreach (['active', 'on_leave', 'no_pay_leave', 'temporary_transfer', 'permanent_transfer', 'secondment', 'deputation', 'acting', 'interdicted', 'suspended', 'resigned', 'retired', 'deceased'] as $s)
                            <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <label style="display:block;margin-top:10px">Reason
                <textarea class="md-input" name="reason" required minlength="10" rows="2"
                    placeholder="Reason for this bulk correction…">
</textarea>
            </label>
            <button class="md-btn--primary" style="margin-top:10px">Apply to Selected Employees</button>
        </div>
        <div class="md-card">
            <div class="md-table-wrap">
                <table class="md-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox"
                                    onclick="document.querySelectorAll('.emp-check').forEach(x=>x.checked=this.checked)">
                            </th>
                            <th>Pay No.</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Unit</th>
                            <th>Subject Code</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $e)
                            <tr>
                                <td>
                                    <input class="emp-check" type="checkbox" name="employee_ids[]"
                                        value="{{ $e->id }}">
                                </td>
                                <td>{{ $e->pay_no }}</td>
                                <td>{{ $e->display_name }}</td>
                                <td>{{ $e->position?->title }}</td>
                                <td>{{ $e->unit?->name }}</td>
                                <td>{{ $e->subjectCode?->code }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $e->employment_status ?? 'active')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="md-card__footer">{{ $employees->links('vendor.pagination.material') }}</div>
        </div>
    </form>
    <script>
        function toggleBulkInputs() {
            let v = document.getElementById('bulkField').value;
            ['unit', 'subject_code', 'employment_status'].forEach(x => document.getElementById('bulk-' + x).style.display =
                'none');
            if (v) document.getElementById('bulk-' + v).style.display = 'block';
        }
    </script>
@endsection
