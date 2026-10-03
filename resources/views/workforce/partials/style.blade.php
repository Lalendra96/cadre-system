<style>
.wf-page{padding:0;color:var(--md-on-surface)}
.wf-header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px;flex-wrap:wrap}
.wf-title{font-size:24px;font-weight:600;margin:0;color:var(--md-on-surface)}
.wf-subtitle{color:var(--md-on-surface-variant);font-size:13px}
.wf-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.wf-card{background:var(--md-surface-container-low);border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-lg);padding:16px;box-shadow:var(--md-elevation-1)}
.wf-kpi{font-size:28px;font-weight:700;color:var(--md-primary)}
.wf-table{width:100%;border-collapse:collapse;background:var(--md-surface-container-low);border-radius:var(--md-shape-md);overflow:hidden}
.wf-table th,.wf-table td{padding:10px;border-bottom:1px solid var(--md-outline-variant);text-align:left;font-size:13px}
.wf-table th{background:var(--md-surface-container);color:var(--md-on-surface-variant);font-weight:600}
.wf-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border-radius:var(--md-shape-full);padding:9px 16px;font-weight:600;text-decoration:none;cursor:pointer;border:0}
.wf-btn-primary{background:var(--md-primary);color:var(--md-on-primary)}
.wf-btn-light{background:var(--md-secondary-container);color:var(--md-on-secondary-container)}
.wf-badge{padding:4px 8px;border-radius:999px;font-size:11px;font-weight:700;background:var(--md-secondary-container);color:var(--md-on-secondary-container)}
.wf-badge.success{background:var(--md-success-container);color:var(--md-on-success-container)}
.wf-badge.warn{background:var(--md-warning-container,#fff3cd);color:var(--md-on-warning-container,#6b4d00)}
.wf-badge.danger{background:var(--md-error-container);color:var(--md-on-error-container)}
.wf-form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.wf-field label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--md-on-surface-variant)}
.wf-field input,.wf-field select,.wf-field textarea{width:100%;box-sizing:border-box;border:1px solid var(--md-outline);border-radius:var(--md-shape-sm);padding:10px 12px;background:var(--md-surface-container-high);color:var(--md-on-surface)}
.wf-field input:focus,.wf-field select:focus,.wf-field textarea:focus{outline:2px solid color-mix(in srgb,var(--md-primary) 30%,transparent);border-color:var(--md-primary)}
.wf-notice{border-left:4px solid var(--md-primary);background:var(--md-primary-container);color:var(--md-on-primary-container);padding:11px 13px;border-radius:var(--md-shape-sm);font-size:13px;margin-bottom:14px}
@media(max-width:1000px){.wf-grid,.wf-form-grid{grid-template-columns:1fr 1fr}}
@media(max-width:640px){.wf-grid,.wf-form-grid{grid-template-columns:1fr}}
</style>
