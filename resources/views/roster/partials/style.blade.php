<style>
.roster-page{padding:0;color:var(--md-on-surface)}
.roster-card{background:var(--md-surface-container-low);border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-lg);padding:16px;box-shadow:var(--md-elevation-1)}
.roster-title{color:var(--md-on-surface);font-weight:600}
.roster-btn-primary,.btn-roster{display:inline-flex;align-items:center;justify-content:center;background:var(--md-primary);border:1px solid var(--md-primary);color:var(--md-on-primary);border-radius:var(--md-shape-full);padding:9px 16px;text-decoration:none;font-weight:600}
.roster-btn-primary:hover,.btn-roster:hover{filter:brightness(1.06);color:var(--md-on-primary)}
.roster-badge{border-radius:999px;padding:4px 8px;font-size:11px;font-weight:700;background:var(--md-secondary-container);color:var(--md-on-secondary-container)}
.roster-table{width:100%;border-collapse:collapse;background:var(--md-surface-container-low)}
.roster-table th,.roster-table td{padding:10px;border-bottom:1px solid var(--md-outline-variant);font-size:13px}
.roster-table th{background:var(--md-surface-container);color:var(--md-on-surface-variant);font-weight:600}
.roster-help{border-left:4px solid var(--md-primary);background:var(--md-primary-container);padding:11px 13px;border-radius:var(--md-shape-sm);color:var(--md-on-primary-container)}
.roster-page input,.roster-page select,.roster-page textarea{background:var(--md-surface-container-high);color:var(--md-on-surface);border:1px solid var(--md-outline);border-radius:var(--md-shape-sm);padding:10px 12px}

.roster-shell{width:100%;box-sizing:border-box}
.roster-shell .container-fluid{padding:0!important}
.roster-shell .row{display:flex;flex-wrap:wrap;margin:-6px}
.roster-shell .row>div{padding:6px;box-sizing:border-box}
.roster-shell .col-12{width:100%}.roster-shell .col-md-1{width:8.333%}.roster-shell .col-md-2{width:16.666%}.roster-shell .col-md-3{width:25%}.roster-shell .col-md-4{width:33.333%}.roster-shell .col-md-5{width:41.666%}.roster-shell .col-md-6{width:50%}.roster-shell .col-lg-4{width:33.333%}.roster-shell .col-lg-8{width:66.666%}
.roster-shell .d-flex{display:flex}.roster-shell .justify-content-between{justify-content:space-between}.roster-shell .align-items-center{align-items:center}.roster-shell .align-items-start{align-items:flex-start}.roster-shell .align-items-end{align-items:flex-end}
.roster-shell .card{background:var(--md-surface-container-low);border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-lg);overflow:hidden}.roster-shell .card-body{padding:16px}.roster-shell .card-header{padding:12px 16px;border-bottom:1px solid var(--md-outline-variant);background:var(--md-surface-container)!important}
.roster-shell .table-responsive{overflow:auto}.roster-shell .table{width:100%;border-collapse:collapse}.roster-shell .table th,.roster-shell .table td{padding:10px;border-bottom:1px solid var(--md-outline-variant);text-align:left}.roster-shell .table th{font-size:12px;color:var(--md-on-surface-variant);font-weight:600}
.roster-shell .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border-radius:var(--md-shape-full);padding:9px 15px;text-decoration:none;font-weight:600;border:1px solid var(--md-outline);background:transparent;color:var(--md-primary);cursor:pointer}.roster-shell .btn-steel,.roster-shell .btn-primary,.roster-shell .btn-success,.roster-shell .btn-dark{background:var(--md-primary);border-color:var(--md-primary);color:var(--md-on-primary)}.roster-shell .btn-outline-primary,.roster-shell .btn-outline-secondary{background:transparent;color:var(--md-primary);border-color:var(--md-outline)}.roster-shell .btn-outline-danger{color:var(--md-error);border-color:var(--md-error)}.roster-shell .btn-sm{padding:6px 11px;font-size:12px}
.roster-shell .form-control,.roster-shell .form-select{width:100%;box-sizing:border-box;background:var(--md-surface-container-high);color:var(--md-on-surface);border:1px solid var(--md-outline);border-radius:var(--md-shape-sm);padding:10px 12px}.roster-shell label{display:block;font-size:12px;font-weight:600;color:var(--md-on-surface-variant);margin-bottom:4px}
.roster-shell .form-check{display:flex;gap:8px;align-items:center}.roster-shell .form-check label{margin:0}.roster-shell .text-muted{color:var(--md-on-surface-variant)!important}.roster-shell .small{font-size:12px}.roster-shell .badge,.roster-shell .status-pill{display:inline-flex;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:700;background:var(--md-secondary-container);color:var(--md-on-secondary-container)}.roster-shell .status-pill.cross-unit{background:var(--md-tertiary-container);color:var(--md-on-tertiary-container)}.roster-shell .status-pill.home-unit{background:var(--md-success-container);color:var(--md-on-success-container)}
.roster-shell .slot-row{padding:14px;margin-bottom:12px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-md);background:var(--md-surface-container-low)}.roster-shell .help-inline{border-left:4px solid var(--md-primary);padding:10px 12px;background:var(--md-primary-container);color:var(--md-on-primary-container);border-radius:var(--md-shape-sm)}
.roster-shell .mt-1{margin-top:4px}.roster-shell .mt-2{margin-top:8px}.roster-shell .mt-3{margin-top:12px}.roster-shell .mt-4{margin-top:16px}.roster-shell .mb-0{margin-bottom:0}.roster-shell .mb-1{margin-bottom:4px}.roster-shell .mb-2{margin-bottom:8px}.roster-shell .mb-3{margin-bottom:12px}.roster-shell .mb-4{margin-bottom:16px}.roster-shell .py-4{padding-top:0!important;padding-bottom:0!important}.roster-shell .w-100{width:100%}
@media(max-width:900px){.roster-shell .col-md-1,.roster-shell .col-md-2,.roster-shell .col-md-3,.roster-shell .col-md-4,.roster-shell .col-md-5,.roster-shell .col-md-6,.roster-shell .col-lg-4,.roster-shell .col-lg-8{width:100%}}
</style>
<style>
/* 2026 roster scheduling refresh — built on the existing MD3 token system. */
.roster-page-header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:18px}
.roster-eyebrow{font-size:11px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--md-primary);margin-bottom:4px}
.roster-subtitle{margin:5px 0 0;color:var(--md-on-surface-variant);font-size:13px;max-width:760px}
.roster-header-actions{display:flex;gap:8px;flex-wrap:wrap}
.roster-gap-8{gap:8px}
.roster-notice{display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;padding:12px 14px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-md);background:var(--md-primary-container);color:var(--md-on-primary-container);font-size:13px}
.roster-notice .material-symbols-outlined{font-size:20px}
.roster-builder .roster-card,.roster-template-builder .roster-card{margin-bottom:16px;padding:0}
.roster-builder .roster-card>.roster-section-heading,.roster-template-builder .roster-card>.roster-section-heading{padding:16px 18px 12px}
.roster-builder .roster-card>.roster-form-grid,.roster-template-builder .roster-card>.roster-form-grid{padding:0 18px 18px}
.roster-section-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.roster-section-heading h3{margin:0;color:var(--md-on-surface);font-size:16px;font-weight:700}
.roster-section-heading p{margin:3px 0 0;color:var(--md-on-surface-variant);font-size:12px}
.roster-step{display:inline-grid;place-items:center;min-width:28px;height:28px;border-radius:999px;background:var(--md-primary-container);color:var(--md-on-primary-container);font-size:12px;font-weight:800}
.roster-form-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.roster-form-grid .field-span-2{grid-column:span 2}.roster-form-grid .field-span-3{grid-column:span 3}.roster-form-grid .field-span-4{grid-column:1/-1}
.schedule-toolbar{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:0 18px 12px}
.employee-search-wrap{min-width:260px;display:flex;align-items:center;gap:6px;border:1px solid var(--md-outline-variant);border-radius:999px;background:var(--md-surface-container);padding:6px 12px}
.employee-search-wrap .material-symbols-outlined{font-size:18px;color:var(--md-on-surface-variant)}
.employee-search-wrap input{border:0!important;background:transparent!important;padding:4px!important;outline:none;width:100%;color:var(--md-on-surface)}
.schedule-summary{display:flex;flex-wrap:wrap;gap:7px;font-size:11px;color:var(--md-on-surface-variant)}
.schedule-summary span{padding:5px 8px;border-radius:999px;background:var(--md-surface-container);border:1px solid var(--md-outline-variant)}
.schedule-summary .summary-warning{color:var(--md-error);background:var(--md-error-container)}
.time-slot-legend{display:flex;align-items:center;flex-wrap:wrap;gap:12px;padding:10px 18px;border-top:1px solid var(--md-outline-variant);border-bottom:1px solid var(--md-outline-variant);background:var(--md-surface-container-lowest);font-size:11px;color:var(--md-on-surface-variant)}
.legend-title{font-weight:800;color:var(--md-on-surface)}
.legend-dot{display:inline-block;width:9px;height:9px;border-radius:3px;margin-right:4px;vertical-align:-1px}
.legend-dot.shift-morning,.slot-colour.shift-morning{background:#2e7d32}.legend-dot.shift-evening,.slot-colour.shift-evening{background:#ef6c00}.legend-dot.shift-night,.slot-colour.shift-night{background:#3949ab}.legend-dot.shift-oncall,.slot-colour.shift-oncall{background:#7b1fa2}.legend-dot.shift-cross,.slot-colour.shift-cross{background:#00838f}.legend-dot.shift-conflict,.slot-colour.shift-conflict{background:var(--md-error)}
.roster-calendar{--calendar-columns:7;display:grid;grid-template-columns:repeat(var(--calendar-columns),minmax(145px,1fr));gap:0;overflow:auto;border-top:1px solid var(--md-outline-variant);min-height:260px}
.calendar-day{min-width:145px;border-right:1px solid var(--md-outline-variant);background:var(--md-surface-container-lowest)}
.calendar-day.is-today{background:var(--md-primary-container)}
.calendar-day-head{width:100%;display:grid;grid-template-columns:1fr auto;grid-template-rows:auto auto;align-items:center;text-align:left;padding:10px 11px;border:0;border-bottom:1px solid var(--md-outline-variant);background:transparent;color:var(--md-on-surface);cursor:pointer}
.calendar-day-head span{font-size:11px;font-weight:700;color:var(--md-on-surface-variant)}.calendar-day-head strong{grid-row:1/3;grid-column:2;font-size:21px}.calendar-day-head small{font-size:10px;color:var(--md-on-surface-variant)}
.calendar-shifts{padding:8px;display:flex;flex-direction:column;gap:6px;min-height:195px}
.calendar-shift{display:flex;align-items:center;gap:7px;border:1px solid transparent;border-radius:10px;padding:7px;text-align:left;cursor:pointer;color:var(--md-on-surface);background:var(--md-surface-container);transition:transform .12s ease,box-shadow .12s ease}
.calendar-shift:hover{transform:translateY(-1px);box-shadow:var(--md-elevation-1)}
.calendar-shift.shift-morning{border-left:4px solid #2e7d32}.calendar-shift.shift-evening{border-left:4px solid #ef6c00}.calendar-shift.shift-night{border-left:4px solid #3949ab}.calendar-shift.shift-oncall{border-left:4px solid #7b1fa2}.calendar-shift.shift-cross{border-left:4px solid #00838f}.calendar-shift.shift-conflict{border-left:4px solid var(--md-error);background:var(--md-error-container);color:var(--md-on-error-container)}
.employee-avatar{display:grid;place-items:center;flex:0 0 30px;width:30px;height:30px;border-radius:999px;background:var(--md-secondary-container);color:var(--md-on-secondary-container);font-size:10px;font-weight:800;letter-spacing:.02em}.employee-avatar--large{width:38px;height:38px;flex-basis:38px;font-size:12px}
.shift-copy{display:flex;min-width:0;flex-direction:column}.shift-copy strong{font-size:10px;white-space:nowrap}.shift-copy small{font-size:9px;color:inherit;opacity:.75;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.shift-warning{margin-left:auto;font-size:16px}
.calendar-add{display:grid;place-items:center;width:100%;min-height:34px;border:1px dashed var(--md-outline);border-radius:9px;color:var(--md-primary);background:transparent;cursor:pointer}.calendar-add:hover{background:var(--md-primary-container)}
.calendar-add .material-symbols-outlined{font-size:18px}.calendar-empty,.assignment-empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;min-height:180px;text-align:center;color:var(--md-on-surface-variant)}
.calendar-empty .material-symbols-outlined,.assignment-empty-state .material-symbols-outlined{font-size:34px;color:var(--md-primary)}
.calendar-actions{display:flex;gap:7px}
.roster-duty-editor #assignmentRows{padding:0 18px 4px}.assignment-empty-state{margin:0 18px 18px;border:1px dashed var(--md-outline-variant);border-radius:var(--md-shape-md)}
.duty-card,.template-slot-card{margin-bottom:12px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-md);background:var(--md-surface-container-lowest);transition:border .2s ease,box-shadow .2s ease}.duty-card.is-highlighted{border-color:var(--md-primary);box-shadow:0 0 0 3px var(--md-primary-container)}
.duty-card-head,.template-slot-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 12px;border-bottom:1px solid var(--md-outline-variant);min-width:0}.duty-person,.template-slot-title{min-width:0;flex:1 1 auto}
.duty-person,.template-slot-title{display:flex;align-items:center;gap:9px;min-width:0;flex:1 1 auto}.duty-person div,.template-slot-title div{display:flex;flex-direction:column}.duty-person strong,.template-slot-title strong{font-size:12px;color:var(--md-on-surface)}.duty-person small,.template-slot-title small{font-size:10px;color:var(--md-on-surface-variant)}
.duty-card-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex:0 0 auto;margin-left:auto;min-width:max-content}.icon-btn{appearance:none;-webkit-appearance:none;display:inline-flex;align-items:center;justify-content:center;flex:0 0 36px;width:36px;min-width:36px;max-width:36px;height:36px;min-height:36px;padding:0;overflow:hidden;border-radius:999px;border:1px solid var(--md-outline-variant);background:var(--md-surface-container-lowest);color:var(--md-primary);cursor:pointer;line-height:1;box-sizing:border-box;transition:background .15s ease,border-color .15s ease,transform .15s ease}.icon-btn:hover{background:var(--md-primary-container);border-color:var(--md-primary)}.icon-btn:active{transform:scale(.96)}.icon-btn:focus-visible{outline:2px solid var(--md-primary);outline-offset:2px}.icon-btn svg{display:block;width:18px;height:18px;flex:0 0 18px;fill:currentColor;pointer-events:none}.icon-btn--danger{color:var(--md-error)}.icon-btn--danger:hover{background:var(--md-error-container);border-color:var(--md-error)}
.roster-form-grid--duty,.roster-form-grid--template{padding:12px!important}.duty-feedback{min-height:0;padding:0 12px 10px;font-size:11px;color:var(--md-on-surface-variant)}.duty-feedback.has-warning{color:var(--md-error);font-weight:600}
.roster-sticky-actions{position:sticky;bottom:10px;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:14px;padding:10px 12px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-lg);background:color-mix(in srgb,var(--md-surface) 92%,transparent);backdrop-filter:blur(10px);box-shadow:var(--md-elevation-2);font-size:12px;color:var(--md-on-surface-variant)}
.coverage-scale{display:flex;justify-content:space-between;padding:14px 18px 5px;font-size:10px;color:var(--md-on-surface-variant)}
.coverage-timeline{position:relative;height:94px;margin:0 18px 18px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-md);overflow:hidden;background:var(--md-surface-container-lowest)}
.timeline-grid{position:absolute;inset:0;background:linear-gradient(to right,transparent 24.8%,var(--md-outline-variant) 25%,transparent 25.2%,transparent 49.8%,var(--md-outline-variant) 50%,transparent 50.2%,transparent 74.8%,var(--md-outline-variant) 75%,transparent 75.2%)}
.timeline-slot{position:absolute;top:18px;height:56px;min-width:54px;border:0;border-radius:10px;padding:7px 9px;display:flex;flex-direction:column;align-items:flex-start;justify-content:center;color:#fff;cursor:pointer;overflow:hidden;box-shadow:var(--md-elevation-1)}
.timeline-slot strong,.timeline-slot small{max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.timeline-slot strong{font-size:10px}.timeline-slot small{font-size:9px;opacity:.85}.timeline-slot.shift-morning{background:#2e7d32}.timeline-slot.shift-evening{background:#ef6c00}.timeline-slot.shift-night{background:#3949ab}.timeline-slot.shift-oncall{background:#7b1fa2}
.template-slot-card{margin:0 18px 12px}.slot-colour{width:9px;height:36px;border-radius:999px;display:block}
@media(max-width:1100px){.roster-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.roster-form-grid .field-span-4{grid-column:1/-1}.roster-calendar{grid-template-columns:repeat(var(--calendar-columns),minmax(130px,1fr))}.schedule-toolbar{align-items:flex-start;flex-direction:column}.schedule-summary{width:100%}}
@media(max-width:700px){.roster-page-header,.roster-section-heading{flex-direction:column}.duty-card-head,.template-slot-head{align-items:flex-start}.duty-card-actions{align-self:flex-start}.roster-header-actions{width:100%}.roster-header-actions .btn{flex:1}.roster-form-grid{grid-template-columns:1fr}.roster-form-grid .field-span-2,.roster-form-grid .field-span-3,.roster-form-grid .field-span-4{grid-column:1}.employee-search-wrap{min-width:0;width:100%;box-sizing:border-box}.roster-sticky-actions{bottom:6px}.roster-sticky-actions span{display:none}}
</style>
<style>
.calendar-day.is-drop-target{outline:2px solid var(--md-primary);outline-offset:-2px;background:var(--md-primary-container)}
.calendar-shift[draggable="true"]{cursor:grab}.calendar-shift[draggable="true"]:active{cursor:grabbing}
</style>
<style>
.roster-page-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px}.roster-page-head h1{margin:0;color:var(--md-on-surface)}.roster-page-head p{margin:5px 0 0;color:var(--md-on-surface-variant);font-size:13px;max-width:820px}
.roster-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.roster-card-head{display:flex;justify-content:space-between;gap:12px;margin-bottom:12px}.roster-card-head h2{font-size:16px;margin:0;color:var(--md-on-surface)}.roster-card-head p{font-size:12px;margin:4px 0 0;color:var(--md-on-surface-variant)}
.roster-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:var(--md-shape-full);padding:8px 14px;text-decoration:none;font-weight:650;border:1px solid var(--md-outline);cursor:pointer;background:transparent;color:var(--md-primary)}.roster-btn-primary{background:var(--md-primary);border-color:var(--md-primary);color:var(--md-on-primary)}.roster-btn-secondary{background:var(--md-surface-container);border-color:var(--md-outline-variant);color:var(--md-primary)}
.roster-alert{padding:10px 12px;border-radius:var(--md-shape-sm);margin-bottom:12px;border:1px solid var(--md-outline-variant)}.roster-alert-success{background:var(--md-success-container);color:var(--md-on-success-container)}.roster-alert-danger{background:var(--md-error-container);color:var(--md-on-error-container)}.roster-table-wrap{overflow:auto}
.roster-form-grid label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:600;color:var(--md-on-surface-variant)}
@media(max-width:900px){.roster-grid-2{grid-template-columns:1fr}.roster-page-head{flex-direction:column}}
</style>
<style>
.calendar-day.has-staffing-shortfall{outline:2px solid var(--md-tertiary,#7d5260);outline-offset:-2px}
.calendar-day.has-hard-shortfall{outline-color:var(--md-error,#ba1a1a)}
.staffing-shortfall-badge{display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:22px;padding:0 7px;border-radius:999px;background:var(--md-error-container,#ffdad6);color:var(--md-on-error-container,#410002);font-size:11px;font-weight:700;margin-top:4px}
</style>
