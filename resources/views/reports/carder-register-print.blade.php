<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Cadre Register — {{ $year }}</title>
<style>
* { margin:0;padding:0;box-sizing:border-box; }
body { font-family:'Times New Roman',serif; font-size:10.5pt; color:#000; background:#fff; }
.page { max-width:297mm; margin:0 auto; padding:15mm 20mm; }
h1 { font-size:14pt; text-align:center; font-weight:bold; margin-bottom:4pt; }
h2 { font-size:11pt; text-align:center; margin-bottom:10pt; }
.meta { font-size:9pt; color:#555; text-align:center; margin-bottom:14pt; }
.position-block { margin-bottom:18pt; page-break-inside: avoid; }
.position-title {
    font-size:11pt; font-weight:bold; padding:4pt 8pt;
    background:#f0f0f0; border:1px solid #999; margin-bottom:0;
}
table { width:100%; border-collapse:collapse; font-size:9.5pt; }
th,td { border:1px solid #999; padding:3pt 6pt; vertical-align:top; }
th { background:#e8e8e8; font-weight:bold; text-align:center; }
td.num { text-align:right; }
.no-data { text-align:center; font-style:italic; color:#888; padding:6pt; font-size:9pt; }
.totals-row td { background:#f8f8f8; font-weight:bold; }
.page-break { page-break-before: always; }
.footer { margin-top:24pt; font-size:9pt; color:#555; }
@media print {
    @page { margin:12mm; size:A4 landscape; }
    .position-block { page-break-inside:avoid; }
}
</style>
</head>
<body>
<div class="page">
    <h1>Institutional Cadre Register</h1>
    <h2>Approved Cadre — Financial Year {{ $year }}</h2>
    <p class="meta">
        Generated: {{ now()->format('d M Y H:i') }} &nbsp;|&nbsp;
        Confidential — Official Use Only
    </p>

    @forelse($positions as $pos)
        @php
            $approvedAmt = $pos->approvedCarders->sum('approved_amount');
            $codes        = $pos->subjectCodes;
        @endphp
        <div class="position-block">
            <div class="position-title">
                {{ $loop->iteration }}. {{ $pos->title }}
                &nbsp;&nbsp;Approved: {{ $approvedAmt }}
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width:80px;">Code</th>
                        <th>Subject Code Name</th>
                        <th style="width:140px;">Assigned Officer</th>
                        <th style="width:60px;text-align:center;">Active</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($codes as $code)
                        @php($officers = $code->users)
                        <tr>
                            <td><strong>{{ $code->code }}</strong></td>
                            <td>{{ $code->name }}</td>
                            <td>
                                @foreach($officers as $o)
                                    <div>{{ $o->name }}</div>
                                @endforeach
                                @if($officers->isEmpty())
                                    <span style="color:#999;font-style:italic;">Unassigned</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                {{ $code->is_active ? '✓' : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="no-data">No subject codes linked to this position.</td></tr>
                    @endforelse
                    <tr class="totals-row">
                        <td colspan="3" style="text-align:right;font-size:9pt;">
                            Total approved for this position:
                        </td>
                        <td class="num">{{ $approvedAmt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <p class="no-data" style="padding:20pt;">No positions found.</p>
    @endforelse

    <div class="footer">
        <p>This document is generated from HIMS PARIKSHA Carder Management System.</p>
        <p style="margin-top:30pt;">
            Authorised Signature: ___________________________
            &nbsp;&nbsp;&nbsp;
            Date: _______________
            &nbsp;&nbsp;&nbsp;
            Designation: ___________________________
        </p>
    </div>
</div>
<script>window.onload = function(){ window.print(); };</script>
</body>
</html>
