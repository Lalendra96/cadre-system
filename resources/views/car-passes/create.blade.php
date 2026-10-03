@extends('layouts.app')

@section('title', 'New Car Pass')

@section('content')
    <div style="margin-bottom:18px;">
        <h2 class="md-headline-sm">New Car Pass</h2>
        <p class="md-body-sm">Search the employee exactly by NIC, Pay No or Phone Number, then prepare the governed pass
            request.</p>
    </div>

    @if ($errors->any())
        <div class="md-card" style="padding:14px;margin-bottom:16px;background:#ffebee;">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!$employee)
        <div class="workforce-panel">
            <div class="panel-title">1. Search Employee</div>
            <form method="GET" action="{{ route('car-passes.search') }}"
                style="margin-top:14px;display:grid;grid-template-columns:180px 1fr auto;gap:10px;align-items:end;">
                <div class="md-form-group">
                    <label class="md-label">Search by</label>
                    <select name="search_type" class="md-input" required>
                        <option value="nic">NIC</option>
                        <option value="pay_no">Pay No</option>
                        <option value="phone">Phone Number</option>
                    </select>
                </div>
                <div class="md-form-group">
                    <label class="md-label">Exact value</label>
                    <input name="search_value" class="md-input" required autocomplete="off"
                        placeholder="Enter exact NIC, Pay No or Phone Number">
                </div>
                <button class="md-btn md-btn--filled">Search</button>
            </form>
            <p class="md-body-sm" style="margin-top:10px;">Wildcard/broad employee searching is intentionally disabled for
                data-minimisation and need-to-know access.</p>
        </div>
    @else
        <div class="workforce-panel" style="margin-bottom:16px;">
            <div class="panel-title">1. Selected Employee</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:12px;">
                <div><span class="md-body-sm">Name</span><br><strong>{{ $employee->name }}</strong></div>
                <div><span class="md-body-sm">Pay No</span><br><strong>{{ $employee->pay_no ?: '—' }}</strong></div>
                <div><span class="md-body-sm">Post</span><br><strong>{{ $employee->position?->title ?: '—' }}</strong></div>
                <div><span class="md-body-sm">Unit</span><br><strong>{{ $employee->unit?->name ?: '—' }}</strong></div>
            </div>
        </div>

        <form method="POST" action="{{ route('car-passes.store') }}">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">

            <div class="workforce-panel" style="margin-bottom:16px;">
                <div class="panel-title">2. Pass Details</div>
                <div class="md-form-row" style="margin-top:12px;">
                    <div class="md-form-group">
                        <label class="md-label">Pass Format *</label>
                        <select name="template_id" class="md-input" required>
                            <option value="">Select format for this post</option>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}" @selected(old('template_id') == $template->id)>
                                    {{ $template->name }} · v{{ $template->version }}
                                </option>
                            @endforeach
                        </select>
                        @if ($templates->isEmpty())
                            <div class="md-body-sm" style="color:#b71c1c;margin-top:6px;">No active pass format is
                                configured for this employee's post. Contact System Admin.</div>
                        @endif
                    </div>
                    <div class="md-form-group">
                        <label class="md-label">Vehicle Registration Number *</label>
                        <input name="vehicle_registration_no" class="md-input" required maxlength="40"
                            value="{{ old('vehicle_registration_no') }}" placeholder="e.g. WP KV 1234">
                    </div>
                    <div class="md-form-group">
                        <label class="md-label">Vehicle Type *</label>
                        <select name="vehicle_type" class="md-input" required>
                            <option value="">Select type</option>
                            @foreach (['car' => 'Car', 'van' => 'Van', 'motorcycle' => 'Motorcycle', 'three_wheeler' => 'Three Wheeler', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('vehicle_type') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="md-form-row">
                    <div class="md-form-group">
                        <label class="md-label">Valid From *</label>
                        <input type="date" name="valid_from" class="md-input" required
                            value="{{ old('valid_from', today()->toDateString()) }}">
                    </div>
                    <div class="md-form-group">
                        <label class="md-label">Valid To *</label>
                        <input type="date" name="valid_to" class="md-input" required
                            value="{{ old('valid_to', today()->addYear()->subDay()->toDateString()) }}">
                    </div>
                    <div class="md-form-group">
                        <label class="md-label">Purpose / Category</label>
                        <input name="purpose" class="md-input" maxlength="500" value="{{ old('purpose') }}"
                            placeholder="Official / Duty">
                    </div>
                </div>
                <div class="md-form-group">
                    <label class="md-label">Notes</label>
                    <textarea name="notes" class="md-input" rows="3" maxlength="1000">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="workforce-panel" style="margin-bottom:16px;background:#eef6ff;">
                <strong>Governed workflow:</strong>
                Draft → Submit for Approval → Independent Approver decision → Approved (locked) → Issue → Revoke/Expire if
                required.
                The preparer cannot approve the same pass.
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <a href="{{ route('car-passes.index') }}" class="md-btn md-btn--text">Cancel</a>
                <button name="submit_action" value="draft" class="md-btn md-btn--outlined"
                    @disabled($templates->isEmpty())>Save Draft</button>
                <button name="submit_action" value="submit" class="md-btn md-btn--filled"
                    @disabled($templates->isEmpty())>Submit for Approval</button>
            </div>
        </form>
    @endif
@endsection
