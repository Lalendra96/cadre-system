<div class="md-form-row">
    <div class="md-form-group">
        <label class="md-label">Rule Code *</label>
        <input
            class="md-input"
            name="code"
            required
            value="{{ old('code', $rule?->code) }}"
            placeholder="e.g. GRADE_PROMOTION_DO"
        >
    </div>

    <div class="md-form-group">
        <label class="md-label">Rule Name *</label>
        <input
            class="md-input"
            name="name"
            required
            value="{{ old('name', $rule?->name) }}"
        >
    </div>

    <div class="md-form-group">
        <label class="md-label">Authority Type *</label>
        <select
            class="md-select"
            name="authority_type"
            required
        >
            @foreach (\App\Models\BusinessRule::AUTHORITY_TYPES as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(
                        old('authority_type', $rule?->authority_type) === $value
                    )
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="md-form-group">
        <label class="md-label">Register Status *</label>
        <select
            class="md-select"
            name="status"
            required
        >
            @foreach (\App\Models\BusinessRule::STATUSES as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(
                        old('status', $rule?->status ?? 'draft') === $value
                    )
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="md-form-group">
    <label class="md-label">
        System Behaviour / Calculation *
    </label>

    <textarea
        class="md-input"
        name="system_behavior"
        rows="3"
        required
        placeholder="Describe exactly what the application calculates, warns about or enforces."
    >{{ old('system_behavior', $rule?->system_behavior) }}</textarea>
</div>

<div class="md-form-row">
    <div class="md-form-group">
        <label class="md-label">Authority Reference</label>
        <input
            class="md-input"
            name="authority_reference"
            value="{{ old('authority_reference', $rule?->authority_reference) }}"
            placeholder="Circular / Gazette / Service Minute / PSC ref."
        >
    </div>

    <div class="md-form-group">
        <label class="md-label">Authority Title</label>
        <input
            class="md-input"
            name="authority_title"
            value="{{ old('authority_title', $rule?->authority_title) }}"
        >
    </div>

    <div class="md-form-group">
        <label class="md-label">Recorded Source URL</label>
        <input
            class="md-input"
            type="url"
            name="authority_url"
            value="{{ old('authority_url', $rule?->authority_url) }}"
            placeholder="https://..."
        >
    </div>
</div>

<div class="md-form-row">
    <div class="md-form-group">
        <label class="md-label">Effective Date</label>
        <input
            class="md-input"
            type="date"
            name="effective_date"
            value="{{
                old(
                    'effective_date',
                    $rule?->effective_date?->format('Y-m-d')
                )
            }}"
        >
    </div>

    <div class="md-form-group">
        <label class="md-label">Review Due Date</label>
        <input
            class="md-input"
            type="date"
            name="review_due_date"
            value="{{
                old(
                    'review_due_date',
                    $rule?->review_due_date?->format('Y-m-d')
                )
            }}"
        >
    </div>

    <div class="md-form-group">
        <label class="md-label">Approval Reference</label>
        <input
            class="md-input"
            name="approval_reference"
            value="{{ old('approval_reference', $rule?->approval_reference) }}"
        >
    </div>
</div>

<div class="md-form-row">
    <div class="md-form-group">
        <label class="md-label">Approved By</label>
        <input
            class="md-input"
            name="approved_by_name"
            value="{{ old('approved_by_name', $rule?->approved_by_name) }}"
        >
    </div>

    <div class="md-form-group">
        <label class="md-label">Approver Designation</label>
        <input
            class="md-input"
            name="approved_by_designation"
            value="{{
                old(
                    'approved_by_designation',
                    $rule?->approved_by_designation
                )
            }}"
        >
    </div>
</div>

<div class="md-form-group">
    <label class="md-label">Notes</label>
    <textarea
        class="md-input"
        name="notes"
        rows="2"
    >{{ old('notes', $rule?->notes) }}</textarea>
</div>

@if ($rule)
    <div class="md-form-group">
        <label class="md-label">
            Reason for this change *
        </label>

        <textarea
            class="md-input"
            name="change_reason"
            rows="2"
            minlength="10"
            maxlength="1000"
            required
            placeholder="Explain why the rule/source/approval record is being changed."
        >{{ old('change_reason') }}</textarea>
    </div>
@endif

<p class="md-body-sm">
    “Source-Verified by Institution” means the responsible institution has
    checked and recorded the source/reference. It is not a legal opinion or
    certification by the software or developer.
</p>
