@extends('layouts.app')

@section('content')
@include('roster.partials.style')

<div class="roster-shell roster-builder" id="rosterBuilder">
    <div class="roster-page-header">
        <div>
            <div class="roster-eyebrow">Workforce Scheduling</div>
            <h2 class="roster-title">Build Roster Plan</h2>
            <p class="roster-subtitle">Plan duties visually, apply reusable templates and identify conflicts before submission.</p>
        </div>
        <div class="roster-header-actions">
            <a href="{{ route('roster.plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" form="rosterForm" class="btn btn-steel">Save Draft</button>
        </div>
    </div>

    <div class="roster-notice">
        <span class="material-symbols-outlined">info</span>
        <div><strong>Cross-unit coverage is roster-only.</strong> An employee keeps their home unit in the employee profile; the Duty Unit records where they will actually work for this assignment.</div>
    </div>

    <form method="post" action="{{ route('roster.plans.store') }}" id="rosterForm">
        @csrf

        <section class="roster-card roster-plan-basics">
            <div class="roster-section-heading">
                <div>
                    <h3>Plan details</h3>
                    <p>Name the roster, choose its period, then build the calendar.</p>
                </div>
                <span class="roster-step">1</span>
            </div>
            <div class="roster-form-grid">
                <div class="field-span-2">
                    <label>Roster title *</label>
                    <input required class="form-control" name="title" value="{{ old('title') }}" placeholder="e.g. OPD Nursing Roster — October 2026">
                </div>
                <div>
                    <label>Planning unit *</label>
                    <select required class="form-select" name="unit_id" id="planningUnit">
                        @foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label>Reusable template</label>
                    <select class="form-select" name="roster_template_id" id="templateSelect">
                        <option value="">Build manually</option>
                        @foreach($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label>Start date *</label>
                    <input type="date" required class="form-control" name="start_date" id="startDate" value="{{ old('start_date') }}">
                </div>
                <div>
                    <label>End date *</label>
                    <input type="date" required class="form-control" name="end_date" id="endDate" value="{{ old('end_date') }}">
                </div>
                <div class="field-span-2">
                    <label>Plan notes</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Coverage requirements, special instructions or planning notes">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>

        <section class="roster-card roster-calendar-section">
            <div class="roster-section-heading roster-section-heading--calendar">
                <div>
                    <div class="d-flex align-items-center roster-gap-8">
                        <span class="roster-step">2</span>
                        <div>
                            <h3>Schedule calendar</h3>
                            <p>Click a day to create a duty. Use initials for fast scanning; open a duty card to see the full employee name.</p>
                        </div>
                    </div>
                </div>
                <div class="calendar-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="todayBtn">This week</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addAssignment">+ Add duty</button>
                </div>
            </div>

            <div class="schedule-toolbar">
                <div class="employee-search-wrap">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="employeeSearch" placeholder="Find employee by name">
                </div>
                <div class="schedule-summary" id="scheduleSummary"></div>
            </div>

            <div class="time-slot-legend" aria-label="Roster legend">
                <span class="legend-title">Legend</span>
                <span><i class="legend-dot shift-morning"></i> Morning</span>
                <span><i class="legend-dot shift-evening"></i> Evening</span>
                <span><i class="legend-dot shift-night"></i> Night</span>
                <span><i class="legend-dot shift-oncall"></i> On-call</span>
                <span><i class="legend-dot shift-cross"></i> Cross-unit</span>
                <span><i class="legend-dot shift-conflict"></i> Conflict / leave</span>
            </div>

            <div class="roster-calendar" id="calendar"></div>
            <div class="calendar-empty" id="calendarEmpty">
                <span class="material-symbols-outlined">calendar_month</span>
                <strong>Choose a start and end date</strong>
                <span>The visual roster calendar will appear here.</span>
            </div>
        </section>

        <section class="roster-card roster-duty-editor">
            <div class="roster-section-heading">
                <div>
                    <h3>Duty details</h3>
                    <p>Fine-tune time, unit, role and instructions. Changes immediately update the calendar.</p>
                </div>
                <span class="roster-step">3</span>
            </div>
            <div id="assignmentRows"></div>
            <div id="assignmentEmpty" class="assignment-empty-state">
                <span class="material-symbols-outlined">person_add</span>
                <strong>No duties added yet</strong>
                <span>Click a calendar day or use “Add duty”.</span>
            </div>
        </section>

        <div class="roster-sticky-actions">
            <div><strong id="footerDutyCount">0 duties</strong><span> saved as draft before approval</span></div>
            <button class="btn btn-steel">Save Draft</button>
        </div>
    </form>
</div>

<script>
(() => {
    const employees = @json($employees);
    const units = @json($units);
    const templates = @json($templates);
    const staffingRules = @json($staffingRules);
    const conflictUrl = @json(route('roster.conflict-check'));
    const csrf = @json(csrf_token());

    let rows = [];
    let filter = '';

    const el = id => document.getElementById(id);
    const escapeHtml = value => String(value ?? '').replace(/[&<>\"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[c]));
    const dateKey = d => d.toISOString().slice(0,10);
    const asDate = value => value ? new Date(value + 'T00:00:00') : null;
    const employeeById = id => employees.find(e => String(e.id) === String(id));
    const unitById = id => units.find(u => String(u.id) === String(id));

    function staffingShortfallsForDay(date) {
        const day = asDate(date);
        if (!day) return [];
        const dow = day.getDay();
        return staffingRules.filter(rule => String(rule.unit_id) === String(el('planningUnit').value) && (rule.day_of_week === null || Number(rule.day_of_week) === dow)).map(rule => {
            const matching = rows.filter(row => {
                if (row.duty_date !== date || String(row.duty_unit_id) !== String(rule.unit_id)) return false;
                if (!(String(row.start_time || '') < String(rule.end_time || '') && String(row.end_time || '') > String(rule.start_time || ''))) return false;
                const emp = employeeById(row.employee_id);
                if (rule.position_id && String(emp?.position_id || '') !== String(rule.position_id)) return false;
                if (rule.competency_id && !(emp?.competency_ids || []).map(String).includes(String(rule.competency_id))) return false;
                return true;
            }).length;
            return {...rule, actual: matching, shortfall: Math.max(0, Number(rule.minimum_staff || 0) - matching)};
        }).filter(x => x.shortfall > 0);
    }

    function initials(employee) {
        const raw = String(employee?.plain_name || employee?.name || '').trim();
        if (!raw) return '?';
        const parts = raw.split(/\s+/).filter(Boolean);
        if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }

    function shiftClass(row) {
        const employee = employeeById(row.employee_id);
        if (row._conflict) return 'shift-conflict';
        if (employee && row.duty_unit_id && String(employee.unit_id) !== String(row.duty_unit_id)) return 'shift-cross';
        const role = String(row.duty_role || '').toLowerCase();
        if (role.includes('on call') || role.includes('on-call')) return 'shift-oncall';
        const hour = Number(String(row.start_time || '08:00').split(':')[0]);
        if (hour >= 18 || hour < 6) return 'shift-night';
        if (hour >= 12) return 'shift-evening';
        return 'shift-morning';
    }

    function datesInRange() {
        const start = asDate(el('startDate').value);
        const end = asDate(el('endDate').value);
        if (!start || !end || end < start) return [];
        const out = [];
        const cursor = new Date(start);
        while (cursor <= end && out.length < 62) {
            out.push(new Date(cursor));
            cursor.setDate(cursor.getDate() + 1);
        }
        return out;
    }

    function employeeOptions(value) {
        const needle = filter.toLowerCase();
        return '<option value="">Select employee</option>' + employees
            .filter(e => !needle || String(e.name).toLowerCase().includes(needle))
            .map(e => `<option value="${e.id}" ${String(e.id)===String(value)?'selected':''}>${escapeHtml(e.name)} · ${initials(e)}</option>`).join('');
    }

    function unitOptions(value) {
        return '<option value="">Select unit</option>' + units.map(u => `<option value="${u.id}" ${String(u.id)===String(value)?'selected':''}>${escapeHtml(u.name)}</option>`).join('');
    }

    function renderCalendar() {
        const days = datesInRange();
        el('calendarEmpty').style.display = days.length ? 'none' : 'flex';
        el('calendar').style.display = days.length ? 'grid' : 'none';
        if (!days.length) return;

        const columns = days.length;
        el('calendar').style.setProperty('--calendar-columns', columns);
        el('calendar').innerHTML = days.map(day => {
            const key = dateKey(day);
            const dayRows = rows.map((r,i)=>({r,i})).filter(x => x.r.duty_date === key);
            const today = key === dateKey(new Date());
            const shortfalls = staffingShortfallsForDay(key);
            const hardShortfall = shortfalls.some(x => x.is_hard_stop);
            return `<div class="calendar-day ${today?'is-today':''} ${shortfalls.length?'has-staffing-shortfall':''} ${hardShortfall?'has-hard-shortfall':''}" data-date="${key}">
                <button type="button" class="calendar-day-head" data-add-date="${key}" title="Add duty on ${key}">
                    <span>${day.toLocaleDateString(undefined,{weekday:'short'})}</span>
                    <strong>${day.getDate()}</strong>
                    <small>${day.toLocaleDateString(undefined,{month:'short'})}</small>
                    ${shortfalls.length?`<span class="staffing-shortfall-badge" title="${escapeHtml(shortfalls.map(x=>`${x.position||'Any position'}${x.competency?' / '+x.competency:''}: short '+x.shortfall).join(' · '))}">${hardShortfall?'!':'△'} ${shortfalls.length}</span>`:''}
                </button>
                <div class="calendar-shifts">
                    ${dayRows.map(({r,i}) => {
                        const emp = employeeById(r.employee_id);
                        const unit = unitById(r.duty_unit_id);
                        const full = emp?.name || 'Employee not selected';
                        return `<button type="button" class="calendar-shift ${shiftClass(r)}" draggable="true" data-drag-index="${i}" data-edit-index="${i}" title="${escapeHtml(full)} · ${escapeHtml(r.start_time||'')}–${escapeHtml(r.end_time||'')} · ${escapeHtml(unit?.name||'')}">
                            <span class="employee-avatar">${initials(emp)}</span>
                            <span class="shift-copy"><strong>${escapeHtml(r.start_time||'--:--')}–${escapeHtml(r.end_time||'--:--')}</strong><small>${escapeHtml(r.duty_role||unit?.name||'Duty')}</small></span>
                            ${r._conflict?'<span class="material-symbols-outlined shift-warning">warning</span>':''}
                        </button>`;
                    }).join('')}
                    <button type="button" class="calendar-add" data-add-date="${key}"><span class="material-symbols-outlined">add</span></button>
                </div>
            </div>`;
        }).join('');

        document.querySelectorAll('[data-add-date]').forEach(btn => btn.addEventListener('click', () => addRow({ duty_date: btn.dataset.addDate })));
        document.querySelectorAll('[data-drag-index]').forEach(card => card.addEventListener('dragstart', event => {
            event.dataTransfer.setData('text/roster-index', card.dataset.dragIndex);
            event.dataTransfer.effectAllowed = 'move';
        }));
        document.querySelectorAll('.calendar-day').forEach(day => {
            day.addEventListener('dragover', event => { event.preventDefault(); day.classList.add('is-drop-target'); });
            day.addEventListener('dragleave', () => day.classList.remove('is-drop-target'));
            day.addEventListener('drop', event => {
                event.preventDefault(); day.classList.remove('is-drop-target');
                const index = Number(event.dataTransfer.getData('text/roster-index'));
                if (!Number.isNaN(index) && rows[index]) { rows[index].duty_date = day.dataset.date; renderAll(); checkConflict(index).then(renderAll); }
            });
        });
        document.querySelectorAll('[data-edit-index]').forEach(btn => btn.addEventListener('click', () => {
            const target = el(`duty-${btn.dataset.editIndex}`);
            target?.scrollIntoView({behavior:'smooth',block:'center'});
            target?.classList.add('is-highlighted');
            setTimeout(()=>target?.classList.remove('is-highlighted'),900);
        }));
        updateSummary();
    }

    function renderRows() {
        el('assignmentEmpty').style.display = rows.length ? 'none' : 'flex';
        el('assignmentRows').innerHTML = rows.map((r,i) => {
            const emp = employeeById(r.employee_id);
            return `<article class="duty-card" id="duty-${i}" data-index="${i}">
                <div class="duty-card-head">
                    <div class="duty-person">
                        <span class="employee-avatar employee-avatar--large">${initials(emp)}</span>
                        <div><strong>${escapeHtml(emp?.name || 'Select employee')}</strong><small>${escapeHtml(r.duty_date || 'Choose duty date')}</small></div>
                    </div>
                    <div class="duty-card-actions">
                        <button type="button" class="icon-btn" data-duplicate="${i}" title="Duplicate duty" aria-label="Duplicate duty"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h11v11H8V8Zm2 2v7h7v-7h-7ZM5 5h10v2H7v8H5V5Z"/></svg></button>
                        <button type="button" class="icon-btn icon-btn--danger" data-remove="${i}" title="Remove duty" aria-label="Remove duty"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h10l-1 11H8L7 9Zm3 2v7h2v-7h-2Zm4 0v7h2v-7h-2Z"/></svg></button>
                    </div>
                </div>
                <div class="roster-form-grid roster-form-grid--duty">
                    <div class="field-span-2"><label>Employee *</label><select required class="form-select duty-input employee-select" data-field="employee_id" name="assignments[${i}][employee_id]">${employeeOptions(r.employee_id)}</select></div>
                    <div><label>Date *</label><input type="date" required class="form-control duty-input" data-field="duty_date" name="assignments[${i}][duty_date]" value="${escapeHtml(r.duty_date)}"></div>
                    <div><label>Start *</label><input type="time" required class="form-control duty-input" data-field="start_time" name="assignments[${i}][start_time]" value="${escapeHtml(r.start_time||'08:00')}"></div>
                    <div><label>End *</label><input type="time" required class="form-control duty-input" data-field="end_time" name="assignments[${i}][end_time]" value="${escapeHtml(r.end_time||'16:00')}"></div>
                    <div class="field-span-2"><label>Duty unit *</label><select required class="form-select duty-input" data-field="duty_unit_id" name="assignments[${i}][duty_unit_id]">${unitOptions(r.duty_unit_id || el('planningUnit').value)}</select></div>
                    <div class="field-span-2"><label>Duty / role</label><input class="form-control duty-input" data-field="duty_role" name="assignments[${i}][duty_role]" value="${escapeHtml(r.duty_role)}" placeholder="e.g. OPD Desk / Night Duty / On-call"></div>
                    <div class="field-span-4"><label>Additional details</label><input class="form-control duty-input" data-field="details" name="assignments[${i}][details]" value="${escapeHtml(r.details)}" placeholder="Handover, coverage reason, location or special instructions"></div>
                </div>
                <div class="duty-feedback ${r._conflict||((r._warnings||[]).length)?'has-warning':''}" id="feedback-${i}">
                    ${r._conflict?'This duty has a compliance hard-stop. ':''}${(r._hardStops||[]).map(escapeHtml).join(' ')} ${(r._warnings||[]).map(escapeHtml).join(' ')}
                </div>
            </article>`;
        }).join('');

        document.querySelectorAll('.duty-card').forEach(card => {
            const index = Number(card.dataset.index);
            card.querySelectorAll('.duty-input').forEach(input => input.addEventListener('change', async () => {
                rows[index][input.dataset.field] = input.value;
                renderCalendar();
                if (['employee_id','duty_date','start_time','end_time'].includes(input.dataset.field)) await checkConflict(index);
                renderRows();
            }));
        });
        document.querySelectorAll('[data-remove]').forEach(btn => btn.addEventListener('click', () => { rows.splice(Number(btn.dataset.remove),1); renderAll(); }));
        document.querySelectorAll('[data-duplicate]').forEach(btn => btn.addEventListener('click', () => {
            const source = rows[Number(btn.dataset.duplicate)];
            rows.splice(Number(btn.dataset.duplicate)+1,0,{...source,_conflict:false,_hardStops:[],_warnings:[]}); renderAll();
        }));
        updateSummary();
    }

    function addRow(seed = {}) {
        rows.push({
            employee_id: '', duty_date: seed.duty_date || el('startDate').value || '', start_time: '08:00', end_time: '16:00',
            duty_unit_id: el('planningUnit').value || '', duty_role: '', details: '', ...seed
        });
        renderAll();
        setTimeout(()=>el(`duty-${rows.length-1}`)?.scrollIntoView({behavior:'smooth',block:'center'}),30);
    }

    async function checkConflict(index) {
        const r = rows[index];
        if (!r?.employee_id || !r?.duty_date || !r?.start_time || !r?.end_time) return;
        const fd = new FormData();
        fd.append('_token',csrf); fd.append('employee_id',r.employee_id); fd.append('duty_date',r.duty_date); fd.append('start_time',r.start_time); fd.append('end_time',r.end_time); if(r.duty_unit_id) fd.append('duty_unit_id',r.duty_unit_id); const emp=employeeById(r.employee_id); if(emp?.position_id) fd.append('position_id',emp.position_id);
        try {
            const response = await fetch(conflictUrl,{method:'POST',body:fd,headers:{'Accept':'application/json'}});
            if (!response.ok) return;
            const data = await response.json();
            rows[index]._conflict = Boolean(data.conflict); rows[index]._hardStops = data.hard_stops || []; rows[index]._warnings = data.warnings || [];
        } catch (_) { /* Non-blocking planning aid. Server validation remains authoritative. */ }
    }

    function applyTemplate() {
        const template = templates.find(t => String(t.id) === String(el('templateSelect').value));
        if (!template) return;
        const start = el('startDate').value;
        const selectedUnit = el('planningUnit').value;
        if (!start) return;
        const base = asDate(start);
        template.slots.forEach(slot => {
            const required = Math.max(1, Number(slot.required_staff || 1));
            for (let count = 0; count < required; count++) {
                rows.push({
                    employee_id:'', duty_date:dateKey(base), start_time:String(slot.start_time||'08:00').slice(0,5), end_time:String(slot.end_time||'16:00').slice(0,5),
                    duty_unit_id:slot.default_duty_unit_id || selectedUnit, duty_role:slot.duty_role || slot.label || '', details:slot.additional_details || ''
                });
            }
        });
        renderAll();
    }

    function updateSummary() {
        const uniqueEmployees = new Set(rows.filter(r=>r.employee_id).map(r=>String(r.employee_id))).size;
        const cross = rows.filter(r => { const e=employeeById(r.employee_id); return e && r.duty_unit_id && String(e.unit_id)!==String(r.duty_unit_id); }).length;
        const warnings = rows.filter(r=>r._conflict||((r._warnings||[]).length)).length;
        const shortfallDays = datesInRange().filter(d => staffingShortfallsForDay(dateKey(d)).length).length;
        el('scheduleSummary').innerHTML = `<span><strong>${rows.length}</strong> duties</span><span><strong>${uniqueEmployees}</strong> employees</span><span><strong>${cross}</strong> cross-unit</span><span class="${warnings?'summary-warning':''}"><strong>${warnings}</strong> compliance warnings</span><span class="${shortfallDays?'summary-warning':''}"><strong>${shortfallDays}</strong> staffing shortfall days</span>`;
        el('footerDutyCount').textContent = `${rows.length} ${rows.length===1?'duty':'duties'}`;
    }

    function renderAll(){ renderCalendar(); renderRows(); }

    el('addAssignment').addEventListener('click',()=>addRow());
    el('templateSelect').addEventListener('change',applyTemplate);
    el('employeeSearch').addEventListener('input',e=>{filter=e.target.value;renderRows();});
    ['startDate','endDate'].forEach(id=>el(id).addEventListener('change',renderCalendar));
    el('planningUnit').addEventListener('change',()=>{ rows.forEach(r=>{if(!r.duty_unit_id)r.duty_unit_id=el('planningUnit').value;}); renderAll(); });
    el('todayBtn').addEventListener('click',()=>{
        const d=new Date(); const day=d.getDay()||7; const monday=new Date(d); monday.setDate(d.getDate()-day+1); const sunday=new Date(monday); sunday.setDate(monday.getDate()+6);
        el('startDate').value=dateKey(monday); el('endDate').value=dateKey(sunday); renderCalendar();
    });

    renderAll();
})();
</script>
@endsection
