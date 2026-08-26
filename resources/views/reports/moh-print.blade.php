<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MoH Carder Summary — {{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</title>
<style>
* { margin:0;padding:0;box-sizing:border-box; }
body { font-family:'Times New Roman',serif; font-size:11pt; color:#000; background:#fff; }
.page { max-width:210mm; margin:0 auto; padding:20mm; }
h1 { font-size:14pt; text-align:center; font-weight:bold; margin-bottom:6pt; }
h2 { font-size:11pt; text-align:center; margin-bottom:4pt; }
.meta { text-align:center; font-size:10pt; color:#555; margin-bottom:14pt; }
table { width:100%; border-collapse:collapse; font-size:10pt; }
th,td { border:1px solid #333; padding:4pt 6pt; }
th { background:#f0f0f0; font-weight:bold; text-align:center; }
td.num { text-align:right; }
tfoot td { font-weight:bold; background:#f8f8f8; }
.footer { margin-top:20pt; font-size:9pt; color:#555; text-align:center; }
@media print { @page { margin:15mm; } }
</style>
</head>
<body>
<div class="page">
    <h1>Institution Cadre Summary Report</h1>
    <h2>{{ \DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</h2>
    <p class="meta">Generated: {{ now()->format('d M Y H:i') }} &nbsp;|&nbsp; Confidential — Official Use Only</p>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Position</th>
                <th>Approved</th>
                <th>Male</th>
                <th>Female</th>
                <th>In Position</th>
                <th>Vacancy</th>
                <th>No-Pay Leave</th>
                <th>Fill %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $r)
            <tr>
                <td style="text-align:center;">{{ $i+1 }}</td>
                <td>{{ $r->title }}</td>
                <td class="num">{{ $r->approved }}</td>
                <td class="num">{{ $r->males }}</td>
                <td class="num">{{ $r->females }}</td>
                <td class="num">{{ $r->in_position }}</td>
                <td class="num">{{ $r->vacancy }}</td>
                <td class="num">{{ $r->no_pay }}</td>
                <td class="num">{{ $r->approved > 0 ? round($r->in_position/$r->approved*100) : '—' }}%</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:center;">TOTAL</td>
                <td class="num">{{ $rows->sum('approved') }}</td>
                <td class="num">{{ $rows->sum('males') }}</td>
                <td class="num">{{ $rows->sum('females') }}</td>
                <td class="num">{{ $rows->sum('in_position') }}</td>
                <td class="num">{{ $rows->sum('vacancy') }}</td>
                <td class="num">{{ $rows->sum('no_pay') }}</td>
                <td class="num">{{ $rows->sum('approved') > 0 ? round($rows->sum('in_position')/$rows->sum('approved')*100) : '—' }}%</td>
            </tr>
        </tfoot>
    </table>

    <p class="footer">This document is generated from HIMS PARIKSHA Carder Management System. Print and sign for official submission.</p>
    <p class="footer" style="margin-top:40pt;">Authorised Signature: ___________________________ &nbsp;&nbsp;&nbsp; Date: _______________</p>
</div>
<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
