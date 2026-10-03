@extends('layouts.app')

@section('title', 'Car Pass Administration')

@section('content')
    <div style="margin-bottom:18px;">
        <h2 class="md-headline-sm">🚗 Car Pass Administration</h2>
        <p class="md-body-sm">System Admin controls feature governance, assigns the single responsible Subject Officer and
            manages pass-format images.</p>
    </div>

    @if (session('success'))
        <div class="md-card" style="padding:12px;margin-bottom:14px;background:#e8f5e9;">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="md-card" style="padding:14px;margin-bottom:16px;background:#ffebee;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="workforce-panel" style="margin-bottom:18px;">
        <div class="panel-title">1. Responsibility & Approval Authority</div>
        <p class="md-body-sm">Exactly one active Subject Officer has operational write access. Reassignment creates a new
            auditable responsibility record.</p>

        <form method="POST" action="{{ route('car-passes.admin.governance') }}" style="margin-top:14px;">
            @csrf
            <div class="md-form-row">
                <div class="md-form-group">
                    <label class="md-label">Responsible Subject Officer *</label>
                    <select name="subject_officer_id" class="md-input" required>
                        <option value="">Select active Subject Officer</option>
                        @foreach ($subjectOfficers as $officer)
                            <option value="{{ $officer->id }}" @selected(old('subject_officer_id', $currentAssignment?->subject_officer_id) == $officer->id)>
                                {{ $officer->name }} — {{ $officer->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md-form-group">
                    <label class="md-label">Effective From *</label>
                    <input type="date" name="effective_from" class="md-input" required
                        value="{{ old('effective_from', today()->toDateString()) }}">
                </div>
                <div class="md-form-group">
                    <label class="md-label">Independent Approval Role *</label>
                    <select name="approval_role" class="md-input" required>
                        @foreach (['admin_group' => 'Admin Group', 'planning_officer' => 'Planning Officer', 'super_admin' => 'Super Admin'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('approval_role', $approvalRole) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="md-form-row">
                <div class="md-form-group">
                    <label class="md-label">Reference No.</label>
                    <input name="reference_no" class="md-input" maxlength="100" value="{{ old('reference_no') }}"
                        placeholder="Appointment / duty assignment reference">
                </div>
                <div class="md-form-group" style="flex:2;">
                    <label class="md-label">Reason / Authority *</label>
                    <input name="reason" class="md-input" required minlength="5" maxlength="1000"
                        value="{{ old('reason') }}" placeholder="Formal authority for assignment/reassignment">
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;">
                <button
                    class="md-btn md-btn--filled">{{ $currentAssignment ? 'Confirm Reassignment' : 'Assign Responsibility' }}</button>
            </div>
        </form>
    </div>

    <div class="workforce-panel" style="margin-bottom:18px;">
        <div class="panel-title">2. Upload Pass Format</div>
        <p class="md-body-sm">Upload image-based pass designs and explicitly map each design to eligible posts. Historical
            use remains preserved if a template is later disabled.</p>

        <form method="POST" action="{{ route('car-passes.admin.templates.store') }}" enctype="multipart/form-data"
            style="margin-top:14px;">
            @csrf
            <div class="md-form-row">
                <div class="md-form-group">
                    <label class="md-label">Template Name *</label>
                    <input name="name" class="md-input" required maxlength="150" value="{{ old('name') }}"
                        placeholder="Medical Officer – Staff Car Pass">
                </div>
                <div class="md-form-group">
                    <label class="md-label">Code *</label>
                    <input name="code" class="md-input" required maxlength="50" value="{{ old('code') }}"
                        placeholder="MO-STAFF">
                </div>
                <div class="md-form-group">
                    <label class="md-label">Default Validity (Months) *</label>
                    <input type="number" name="default_validity_months" class="md-input" min="1" max="60"
                        required value="{{ old('default_validity_months', 12) }}">
                </div>
            </div>
            <div class="md-form-row">
                <div class="md-form-group">
                    <label class="md-label">Pass Image *</label>
                    <input type="file" name="image" class="md-input" required accept="image/png,image/jpeg,image/webp">
                </div>
                <div class="md-form-group" style="flex:2;min-width:420px;">
                    <div class="car-pass-post-picker" data-post-picker>
                        <div class="car-pass-post-picker__heading">
                            <div>
                                <label class="md-label" for="eligible-post-search">Eligible Posts *</label>
                                <div class="md-body-sm">Choose every post that is authorised to use this pass format.</div>
                            </div>
                            <span class="car-pass-post-picker__count" data-selected-count>0 selected</span>
                        </div>

                        <div class="car-pass-post-picker__toolbar">
                            <div class="car-pass-post-picker__search-wrap">
                                <span class="car-pass-post-picker__search-icon" aria-hidden="true">⌕</span>
                                <input id="eligible-post-search" type="search"
                                    class="md-input car-pass-post-picker__search"
                                    placeholder="Search by post title or code…" autocomplete="off" data-post-search>
                            </div>
                            <button type="button" class="md-btn md-btn--outlined car-pass-post-picker__action"
                                data-select-visible>
                                Select visible
                            </button>
                            <button type="button" class="md-btn md-btn--text car-pass-post-picker__action"
                                data-clear-posts>
                                Clear
                            </button>
                        </div>

                        <div class="car-pass-post-picker__selected" data-selected-posts aria-live="polite">
                            <span class="car-pass-post-picker__empty">No posts selected yet.</span>
                        </div>

                        <div class="car-pass-post-picker__list" data-post-list>
                            @foreach ($positions as $position)
                                @php
                                    $positionLabel =
                                        $position->title . ($position->code ? ' (' . $position->code . ')' : '');
                                    $isSelectedPosition = in_array(
                                        (string) $position->id,
                                        array_map('strval', old('position_ids', [])),
                                        true,
                                    );
                                @endphp
                                <label class="car-pass-post-picker__option" data-post-option
                                    data-search-text="{{ strtolower($positionLabel) }}"
                                    data-post-label="{{ $positionLabel }}">
                                    <input type="checkbox" name="position_ids[]" value="{{ $position->id }}"
                                        @checked($isSelectedPosition) data-post-checkbox>
                                    <span class="car-pass-post-picker__option-copy">
                                        <strong>{{ $position->title }}</strong>
                                        @if ($position->code)
                                            <span>{{ $position->code }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div class="car-pass-post-picker__footer">
                            <span data-visible-count>{{ $positions->count() }} posts available</span>
                            <span>Selections are saved with the template version for auditability.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;">
                <button class="md-btn md-btn--filled">Upload Template</button>
            </div>
        </form>
    </div>

    <div class="workforce-panel" style="margin-bottom:18px;">
        <div class="panel-title">3. Pass Templates</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-top:14px;">
            @forelse ($templates as $template)
                <div class="md-card" style="padding:14px;">
                    <img src="{{ route('car-passes.admin.templates.image', $template) }}" alt="{{ $template->name }}"
                        style="width:100%;height:150px;object-fit:contain;background:#f5f7fa;border-radius:8px;">
                    <div style="margin-top:10px;"><strong>{{ $template->name }}</strong></div>
                    <div class="md-body-sm">{{ $template->code }} · v{{ $template->version }} ·
                        {{ $template->is_active ? 'Active' : 'Disabled' }}</div>
                    <form method="POST" action="{{ route('car-passes.admin.templates.toggle', $template) }}"
                        style="margin-top:10px;">
                        @csrf
                        @method('PATCH')
                        <input name="reason" class="md-input" required minlength="5" maxlength="1000"
                            placeholder="Reason required">
                        <button class="md-btn md-btn--outlined"
                            style="margin-top:8px;">{{ $template->is_active ? 'Disable' : 'Re-enable' }}</button>
                    </form>
                </div>
            @empty
                <div class="md-body-sm">No Car Pass templates uploaded yet.</div>
            @endforelse
        </div>
    </div>

    <div class="workforce-panel">
        <div class="panel-title">4. Responsibility History</div>
        <div style="overflow:auto;margin-top:12px;">
            <table class="md-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Subject Officer</th>
                        <th>Effective From</th>
                        <th>Effective To</th>
                        <th>Assigned By</th>
                        <th>Reason / Authority</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assignmentHistory as $assignment)
                        <tr>
                            <td>{{ $assignment->subjectOfficer?->name ?: '—' }}</td>
                            <td>{{ $assignment->effective_from?->format('d M Y') }}</td>
                            <td>{{ $assignment->effective_to?->format('d M Y') ?: 'Current' }}</td>
                            <td>{{ $assignment->assignedBy?->name ?: '—' }}</td>
                            <td>{{ $assignment->reason }}</td>
                            <td>{{ $assignment->reference_no ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No assignment history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .car-pass-post-picker {
            border: 1px solid var(--md-outline-variant);
            border-radius: 12px;
            background: var(--md-surface-container-low);
            overflow: hidden;
        }

        .car-pass-post-picker__heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px 10px;
            background: var(--md-surface-container);
            border-bottom: 1px solid var(--md-outline-variant);
        }

        .car-pass-post-picker__count {
            flex: 0 0 auto;
            padding: 5px 10px;
            border-radius: 999px;
            background: var(--md-primary-container);
            color: var(--md-on-primary-container);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .car-pass-post-picker__toolbar {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) auto auto;
            gap: 8px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--md-outline-variant);
        }

        .car-pass-post-picker__search-wrap {
            position: relative;
        }

        .car-pass-post-picker__search {
            padding-left: 36px;
        }

        .car-pass-post-picker__search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--md-on-surface-variant);
            pointer-events: none;
        }

        .car-pass-post-picker__action {
            min-height: 40px;
            white-space: nowrap;
        }

        .car-pass-post-picker__selected {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            min-height: 48px;
            padding: 10px 16px;
            background: var(--md-surface-container-low);
            border-bottom: 1px solid var(--md-outline-variant);
        }

        .car-pass-post-picker__chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            max-width: 100%;
            padding: 5px 9px;
            border-radius: 999px;
            background: var(--md-primary-container);
            color: var(--md-on-primary-container);
            font-size: 12px;
            font-weight: 600;
        }

        .car-pass-post-picker__chip span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 260px;
        }

        .car-pass-post-picker__chip button {
            appearance: none;
            border: 0;
            background: transparent;
            color: var(--md-on-primary-container);
            cursor: pointer;
            padding: 0;
            line-height: 1;
            font-size: 15px;
        }

        .car-pass-post-picker__empty {
            align-self: center;
            color: var(--md-on-surface-variant);
            font-size: 12px;
        }

        .car-pass-post-picker__list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1px;
            max-height: 300px;
            overflow-y: auto;
            background: var(--md-outline-variant);
        }

        .car-pass-post-picker__option {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            padding: 9px 14px;
            background: var(--md-surface-container-low);
            cursor: pointer;
            transition: background 120ms ease, box-shadow 120ms ease;
        }

        .car-pass-post-picker__option:hover {
            background: var(--md-surface-container-high);
        }

        .car-pass-post-picker__option:has(input:checked) {
            background: var(--md-primary-container);
            box-shadow: inset 3px 0 var(--md-primary);
        }

        .car-pass-post-picker__option[hidden] {
            display: none;
        }

        .car-pass-post-picker__option input {
            width: 18px;
            height: 18px;
            margin: 0;
            accent-color: var(--md-primary);
            flex: 0 0 auto;
        }

        .car-pass-post-picker__option-copy {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
            color: #173a5e;
        }

        .car-pass-post-picker__option-copy strong {
            font-size: 13px;
            font-weight: 650;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .car-pass-post-picker__option-copy span {
            color: var(--md-on-surface-variant);
            font-size: 11px;
            letter-spacing: .02em;
        }

        .car-pass-post-picker__footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 16px;
            background: var(--md-surface-container);
            color: #60758b;
            font-size: 11px;
        }

        @media (max-width: 900px) {
            .car-pass-post-picker__toolbar {
                grid-template-columns: 1fr 1fr;
            }

            .car-pass-post-picker__search-wrap {
                grid-column: 1 / -1;
            }

            .car-pass-post-picker__list {
                grid-template-columns: 1fr;
            }

            .car-pass-post-picker__footer {
                flex-direction: column;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-post-picker]').forEach(function(picker) {
                const search = picker.querySelector('[data-post-search]');
                const options = Array.from(picker.querySelectorAll('[data-post-option]'));
                const checkboxes = Array.from(picker.querySelectorAll('[data-post-checkbox]'));
                const selectedContainer = picker.querySelector('[data-selected-posts]');
                const selectedCount = picker.querySelector('[data-selected-count]');
                const visibleCount = picker.querySelector('[data-visible-count]');
                const selectVisible = picker.querySelector('[data-select-visible]');
                const clearButton = picker.querySelector('[data-clear-posts]');

                function visibleOptions() {
                    return options.filter(function(option) {
                        return !option.hidden;
                    });
                }

                function renderSelected() {
                    const selected = checkboxes.filter(function(checkbox) {
                        return checkbox.checked;
                    });

                    selectedCount.textContent = selected.length + ' selected';
                    selectedContainer.innerHTML = '';

                    if (selected.length === 0) {
                        const empty = document.createElement('span');
                        empty.className = 'car-pass-post-picker__empty';
                        empty.textContent = 'No posts selected yet.';
                        selectedContainer.appendChild(empty);
                        return;
                    }

                    selected.forEach(function(checkbox) {
                        const option = checkbox.closest('[data-post-option]');
                        const chip = document.createElement('span');
                        chip.className = 'car-pass-post-picker__chip';

                        const label = document.createElement('span');
                        label.textContent = option.dataset.postLabel;
                        chip.appendChild(label);

                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.setAttribute('aria-label', 'Remove ' + option.dataset.postLabel);
                        remove.textContent = '×';
                        remove.addEventListener('click', function() {
                            checkbox.checked = false;
                            renderSelected();
                        });
                        chip.appendChild(remove);

                        selectedContainer.appendChild(chip);
                    });
                }

                function applySearch() {
                    const term = (search.value || '').trim().toLowerCase();

                    options.forEach(function(option) {
                        option.hidden = term !== '' && !option.dataset.searchText.includes(term);
                    });

                    const count = visibleOptions().length;
                    visibleCount.textContent = count + (count === 1 ? ' post available' :
                        ' posts available');
                }

                search.addEventListener('input', applySearch);

                checkboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', renderSelected);
                });

                selectVisible.addEventListener('click', function() {
                    visibleOptions().forEach(function(option) {
                        option.querySelector('[data-post-checkbox]').checked = true;
                    });
                    renderSelected();
                });

                clearButton.addEventListener('click', function() {
                    checkboxes.forEach(function(checkbox) {
                        checkbox.checked = false;
                    });
                    renderSelected();
                });

                renderSelected();
                applySearch();
            });
        });
    </script>

@endsection
