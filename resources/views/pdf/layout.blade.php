<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $reportTitle ?? 'Report' }} — Teaching Hospital Peradeniya</title>
<style>
/* ── Reset & base ─────────────────────────────────────────────────────── */
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    color: #1a1a1a;
    background: #fff;
    line-height: 1.4;
}

/* ── Letterhead ───────────────────────────────────────────────────────── */
.letterhead {
    border-bottom: 3px solid #1565C0;
    padding-bottom: 10px;
    margin-bottom: 14px;
}

.letterhead-top {
    text-align: center;
    margin-bottom: 6px;
}

.hospital-name {
    font-size: 14pt;
    font-weight: bold;
    color: #1565C0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.department-name {
    font-size: 10pt;
    color: #333;
    margin-top: 2px;
}

.system-name {
    font-size: 8pt;
    color: #666;
    margin-top: 2px;
}

.letterhead-meta {
    overflow: hidden;
    margin-top: 8px;
    font-size: 8.5pt;
    color: #444;
}

.letterhead-meta-left  { float: left;  }
.letterhead-meta-right { float: right; text-align: right; }

/* ── Report title ─────────────────────────────────────────────────────── */
.report-title {
    font-size: 13pt;
    font-weight: bold;
    color: #1565C0;
    text-align: center;
    margin-bottom: 4px;
}

.report-subtitle {
    font-size: 9pt;
    color: #555;
    text-align: center;
    margin-bottom: 14px;
}

/* ── Tables ───────────────────────────────────────────────────────────── */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
    margin-bottom: 12px;
}

thead tr {
    background: #1565C0;
    color: #fff;
}

thead th {
    padding: 6px 8px;
    text-align: left;
    font-weight: bold;
    font-size: 8.5pt;
    white-space: nowrap;
}

thead th.text-right,
tbody td.text-right,
tfoot td.text-right {
    text-align: right;
}

thead th.text-center,
tbody td.text-center {
    text-align: center;
}

tbody tr:nth-child(even) { background: #f0f4ff; }
tbody tr:nth-child(odd)  { background: #ffffff; }

tbody td {
    padding: 5px 8px;
    border-bottom: 1px solid #dde3f0;
}

tfoot tr {
    background: #e8edf8;
    font-weight: bold;
}

tfoot td {
    padding: 6px 8px;
    border-top: 2px solid #1565C0;
}

/* ── Status indicators ────────────────────────────────────────────────── */
.badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 3px;
    font-size: 7.5pt;
    font-weight: bold;
}

.badge-danger   { background: #ffebee; color: #b71c1c; border: 1px solid #ef9a9a; }
.badge-warning  { background: #fff8e1; color: #e65100; border: 1px solid #ffcc02; }
.badge-success  { background: #e8f5e9; color: #1b5e20; border: 1px solid #a5d6a7; }
.badge-info     { background: #e3f2fd; color: #0d47a1; border: 1px solid #90caf9; }
.badge-neutral  { background: #f5f5f5; color: #424242; border: 1px solid #bdbdbd; }
.badge-carried  { background: #f3e5f5; color: #4a148c; border: 1px solid #ce93d8; }

/* ── Vacancy highlight ────────────────────────────────────────────────── */
.vacancy-critical { color: #b71c1c; font-weight: bold; }
.vacancy-warn     { color: #e65100; }
.vacancy-ok       { color: #1b5e20; }

/* ── Signature block ──────────────────────────────────────────────────── */
.signature-block {
    margin-top: 40px;
    overflow: hidden;
    page-break-inside: avoid;
}

.sig-col {
    width: 33%;
    float: left;
    text-align: center;
    padding: 0 10px;
}

.sig-line {
    border-top: 1px solid #333;
    margin: 0 10px 4px;
}

.sig-name  { font-weight: bold; font-size: 9pt; }
.sig-title { font-size: 8pt; color: #555; }

/* ── Footer ───────────────────────────────────────────────────────────── */
.report-footer {
    border-top: 1px solid #ccd5e8;
    margin-top: 20px;
    padding-top: 6px;
    overflow: hidden;
    font-size: 7.5pt;
    color: #888;
}

.footer-left    { float: left; }
.footer-right   { float: right; }
.footer-center  { text-align: center; }

.confidential {
    text-align: center;
    font-size: 7pt;
    color: #999;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-top: 4px;
}

/* ── Utility ──────────────────────────────────────────────────────────── */
.page-break { page-break-after: always; }
.no-break   { page-break-inside: avoid; }
.text-muted { color: #888; }
.bold       { font-weight: bold; }
.mb-8       { margin-bottom: 8px; }
.mt-12      { margin-top: 12px; }
</style>
</head>
<body>

{{-- ── Letterhead ────────────────────────────────────────────────────── --}}
<div class="letterhead">
    <div class="letterhead-top">
        <div class="hospital-name">Teaching Hospital Peradeniya</div>
        <div class="department-name">Human Resources Division</div>
        <div class="system-name">HIMS PARIKSHA — Carder Management System</div>
    </div>
    <div class="letterhead-meta">
        <div class="letterhead-meta-left">
            <strong>Generated by:</strong> {{ auth()->user()?->display_name ?? auth()->user()?->name ?? 'System' }}<br>
            <strong>Role:</strong> {{ auth()->user()?->role_label ?? '—' }}
        </div>
        <div class="letterhead-meta-right">
            <strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}<br>
            <strong>Ref:</strong> THP/HRD/{{ now()->format('Y') }}/{{ str_pad((string) (auth()->id() ?? 0), 4, '0', STR_PAD_LEFT) }}
        </div>
    </div>
</div>

{{-- ── Report title injected by child ──────────────────────────────── --}}
<div class="report-title">{{ $reportTitle ?? 'Report' }}</div>
<div class="report-subtitle">{{ $reportSubtitle ?? '' }}</div>

{{-- ── Report body ───────────────────────────────────────────────────── --}}
@yield('content')

{{-- ── Signature block ──────────────────────────────────────────────── --}}
@if(!isset($hideSignature) || !$hideSignature)
<div class="signature-block no-break">
    <div class="sig-col">
        <div class="sig-line"></div>
        <div class="sig-name">Subject Officer</div>
        <div class="sig-title">Submitting Officer</div>
    </div>
    <div class="sig-col">
        <div class="sig-line"></div>
        <div class="sig-name">Planning Officer</div>
        <div class="sig-title">Reviewed &amp; Verified</div>
    </div>
    <div class="sig-col">
        <div class="sig-line"></div>
        <div class="sig-name">Director / DDG</div>
        <div class="sig-title">Authorized Signatory</div>
    </div>
</div>
@endif

{{-- ── Footer ────────────────────────────────────────────────────────── --}}
<div class="report-footer">
    <div class="footer-left">Teaching Hospital Peradeniya &mdash; HIMS PARIKSHA</div>
    <div class="footer-right">{{ now()->format('d M Y') }}</div>
</div>
<div class="confidential">
    Confidential &mdash; For Official Use Only &mdash; Not for Public Distribution
</div>

</body>
</html>
