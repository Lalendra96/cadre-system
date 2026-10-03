@extends('layouts.app')

@section('content')
@include('roster.partials.style')
@php($editing = isset($template))

<div class="roster-shell roster-template-builder">
    <div class="roster-page-header">
        <div>
            <div class="roster-eyebrow">Reusable Scheduling Pattern</div>
            <h2 class="roster-title">{{ $editing ? 'Edit' : 'Create' }} Roster Template</h2>
            <p class="roster-subtitle">Build a repeatable shift pattern once, preview the time coverage, then apply it to future roster plans.</p>
        </div>
        <div class="roster-header-actions">
            <a href="{{ route('roster.templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" form="templateForm" class="btn btn-steel">Save Template</button>
        </div>
    </div>

    <form method="post" id="templateForm" action="{{ $editing ? route('roster.templates.update',$template) : route('roster.templates.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif

        <section class="roster-card">
            <div class="roster-section-heading">
                <div><h3>Template details</h3><p>Templates define time-slot patterns; employees are selected when a roster plan is built.</p></div>
                <span class="roster-step">1</span>
            </div>
            <div class="roster-form-grid">
                <div class="field-span-2"><label>Template name *</label><input required class="form-control" name="name" value="{{ old('name',$template->name??'') }}" placeholder="e.g. Standard 24-hour Ward Coverage"></div>
                <div><label>Home unit *</label><select required class="form-select" name="unit_id">@foreach($units as $u)<option value="{{ $u->id }}" @selected(old('unit_id',$template->unit_id??null)==$u->id)>{{ $u->name }}</option>@endforeach</select></div>
                <div><label>Template type</label><select class="form-select" name="assignment_type"><option value="regular" @selected(old('assignment_type',$template->assignment_type??'regular')==='regular')>Regular</option><option value="on_call" @selected(old('assignment_type',$template->assignment_type??'')==='on_call')>On-call</option><option value="special" @selected(old('assignment_type',$template->assignment_type??'')==='special')>Special</option></select></div>
                <div class="field-span-4"><label>Description</label><textarea class="form-control" name="description" rows="2" placeholder="Explain when this template should be used">{{ old('description',$template->description??'') }}</textarea></div>
            </div>
        </section>

        <section class="roster-card">
            <div class="roster-section-heading">
                <div><div class="d-flex align-items-center roster-gap-8"><span class="roster-step">2</span><div><h3>24-hour coverage map</h3><p>A custom visual calendar shows where each configured time slot sits across the day.</p></div></div></div>
                <button type="button" id="addSlot" class="btn btn-outline-primary btn-sm">+ Add time slot</button>
            </div>

            <div class="time-slot-legend">
                <span class="legend-title">Time slot legend</span>
                <span><i class="legend-dot shift-morning"></i> Morning</span>
                <span><i class="legend-dot shift-evening"></i> Evening</span>
                <span><i class="legend-dot shift-night"></i> Night</span>
                <span><i class="legend-dot shift-oncall"></i> On-call / Special</span>
            </div>

            <div class="coverage-scale"><span>00:00</span><span>06:00</span><span>12:00</span><span>18:00</span><span>24:00</span></div>
            <div class="coverage-timeline" id="coverageTimeline"></div>
        </section>

        <section class="roster-card">
            <div class="roster-section-heading"><div><h3>Time slot details</h3><p>Set staffing requirements, target unit/position and operational instructions.</p></div><span class="roster-step">3</span></div>
            <div id="slots"></div>
        </section>

        <div class="roster-sticky-actions">
            <div><strong id="slotCount">0 time slots</strong><span> in this reusable template</span></div>
            <button class="btn btn-steel">Save Template</button>
        </div>
    </form>
</div>

<script>
(() => {
    const units = @json($units);
    const positions = @json($positions);
    let slots = @json(old('slots',isset($template)?$template->slots->toArray():[]));
    const esc = v => String(v??'').replace(/[&<>\"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[c]));
    const opts = (items,val,key) => '<option value="">—</option>'+items.map(x=>`<option value="${x.id}" ${String(x.id)==String(val)?'selected':''}>${esc(x[key])}</option>`).join('');
    const minutes = time => { const [h,m]=String(time||'00:00').split(':').map(Number); return (h||0)*60+(m||0); };
    function typeClass(slot){ const h=Number(String(slot.start_time||'08:00').slice(0,2)); const text=String(slot.label||slot.duty_role||'').toLowerCase(); if(text.includes('on-call')||text.includes('on call')||text.includes('special'))return 'shift-oncall'; if(h>=18||h<6)return 'shift-night'; if(h>=12)return 'shift-evening'; return 'shift-morning'; }

    function renderTimeline(){
        const timeline=document.getElementById('coverageTimeline');
        timeline.innerHTML = '<div class="timeline-grid"></div>' + slots.map((s,i)=>{
            let start=minutes(s.start_time), end=minutes(s.end_time); if(end<=start)end+=1440;
            const left=(start/1440)*100, width=Math.min(((end-start)/1440)*100,100-left);
            return `<button type="button" class="timeline-slot ${typeClass(s)}" data-slot-jump="${i}" style="left:${left}%;width:${Math.max(width,4)}%" title="${esc(s.label||'Time slot')}"><strong>${esc(s.label||'Slot '+(i+1))}</strong><small>${esc(String(s.start_time||'').slice(0,5))}–${esc(String(s.end_time||'').slice(0,5))}</small></button>`;
        }).join('');
        document.querySelectorAll('[data-slot-jump]').forEach(b=>b.addEventListener('click',()=>document.getElementById(`slot-${b.dataset.slotJump}`)?.scrollIntoView({behavior:'smooth',block:'center'})));
        document.getElementById('slotCount').textContent=`${slots.length} ${slots.length===1?'time slot':'time slots'}`;
    }

    function render(){
        document.getElementById('slots').innerHTML=slots.map((s,i)=>`<article class="template-slot-card" id="slot-${i}">
            <div class="template-slot-head"><div class="template-slot-title"><span class="slot-colour ${typeClass(s)}"></span><div><strong>${esc(s.label||'New time slot')}</strong><small>${esc(String(s.start_time||'').slice(0,5))}–${esc(String(s.end_time||'').slice(0,5))}</small></div></div><div class="duty-card-actions"><button type="button" class="icon-btn" data-copy="${i}" title="Duplicate time slot" aria-label="Duplicate time slot"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h11v11H8V8Zm2 2v7h7v-7h-7ZM5 5h10v2H7v8H5V5Z"/></svg></button><button type="button" class="icon-btn icon-btn--danger" data-delete="${i}" title="Delete time slot" aria-label="Delete time slot"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h10l-1 11H8L7 9Zm3 2v7h2v-7h-2Zm4 0v7h2v-7h-2Z"/></svg></button></div></div>
            <div class="roster-form-grid roster-form-grid--template">
                <div class="field-span-2"><label>Slot label *</label><input class="form-control slot-input" data-index="${i}" data-key="label" name="slots[${i}][label]" value="${esc(s.label)}" required placeholder="Morning / Evening / Night / On-call"></div>
                <div><label>Start *</label><input type="time" class="form-control slot-input" data-index="${i}" data-key="start_time" name="slots[${i}][start_time]" value="${esc(String(s.start_time||'').slice(0,5))}" required></div>
                <div><label>End *</label><input type="time" class="form-control slot-input" data-index="${i}" data-key="end_time" name="slots[${i}][end_time]" value="${esc(String(s.end_time||'').slice(0,5))}" required></div>
                <div class="field-span-2"><label>Default duty unit</label><select class="form-select slot-input" data-index="${i}" data-key="default_duty_unit_id" name="slots[${i}][default_duty_unit_id]">${opts(units,s.default_duty_unit_id,'name')}</select></div>
                <div class="field-span-2"><label>Required position</label><select class="form-select slot-input" data-index="${i}" data-key="position_id" name="slots[${i}][position_id]">${opts(positions,s.position_id,'title')}</select></div>
                <div class="field-span-2"><label>Duty / role</label><input class="form-control slot-input" data-index="${i}" data-key="duty_role" name="slots[${i}][duty_role]" value="${esc(s.duty_role)}" placeholder="Role performed during this slot"></div>
                <div><label>Required staff</label><input type="number" min="1" max="100" class="form-control slot-input" data-index="${i}" data-key="required_staff" name="slots[${i}][required_staff]" value="${esc(s.required_staff||1)}"></div>
                <div class="field-span-4"><label>Operational instructions</label><input class="form-control slot-input" data-index="${i}" data-key="additional_details" name="slots[${i}][additional_details]" value="${esc(s.additional_details)}" placeholder="Handover, location, special responsibilities or coverage notes"></div>
            </div>
        </article>`).join('');
        document.querySelectorAll('.slot-input').forEach(input=>input.addEventListener('input',()=>{ slots[Number(input.dataset.index)][input.dataset.key]=input.value; renderTimeline(); }));
        document.querySelectorAll('[data-delete]').forEach(b=>b.addEventListener('click',()=>{slots.splice(Number(b.dataset.delete),1);render();renderTimeline();}));
        document.querySelectorAll('[data-copy]').forEach(b=>b.addEventListener('click',()=>{const i=Number(b.dataset.copy);slots.splice(i+1,0,{...slots[i],label:(slots[i].label||'Slot')+' Copy'});render();renderTimeline();}));
        renderTimeline();
    }
    document.getElementById('addSlot').addEventListener('click',()=>{slots.push({label:'New Duty',start_time:'08:00',end_time:'16:00',required_staff:1});render();setTimeout(()=>document.getElementById(`slot-${slots.length-1}`)?.scrollIntoView({behavior:'smooth'}),30);});
    if(!slots.length) slots=[{label:'Morning Duty',start_time:'08:00',end_time:'12:00',required_staff:1},{label:'Evening Duty',start_time:'12:00',end_time:'18:00',required_staff:1},{label:'Night Duty',start_time:'18:00',end_time:'08:00',required_staff:1}];
    render();
})();
</script>
@endsection
