<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $letter->title }}</title>
    <style>
        @page {
            margin: 22mm 20mm 22mm 20mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111827;
            line-height: 1.55;
        }

        .header {
            text-align: center;
            border-bottom: 1px solid #9ca3af;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .logo {
            max-width: 74px;
            max-height: 74px;
            margin-bottom: 6px;
        }

        .institution {
            font-size: 19px;
            font-weight: 700;
        }

        .ministry {
            font-size: 13px;
            margin-top: 2px;
        }

        .small {
            font-size: 10px;
            color: #4b5563;
        }

        .meta {
            width: 100%;
            margin-bottom: 18px;
            border-collapse: collapse;
        }

        .meta td {
            width: 50%;
            vertical-align: top;
        }

        .meta td:last-child {
            text-align: right;
        }

        .title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            text-decoration: underline;
            margin: 18px 0;
        }

        .body {
            white-space: pre-wrap;
            min-height: 260px;
        }

        .signature {
            margin-top: 32px;
        }

        .signature img {
            max-width: 180px;
            max-height: 72px;
        }

        .proof {
            margin-top: 18px;
            padding: 9px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            font-size: 9px;
        }

        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -10mm;
            text-align: center;
            font-size: 8px;
            color: #475569;
        }

        .classification {
            font-weight: 700;
            letter-spacing: .3px;
        }
    </style>
</head>

<body>
    @php
        $headData = $letter->officialLetterheadData();
    @endphp
    @php
        $head = $headData !== [] ? (object) $headData : null;
    @endphp
    <div class="header">
        @if ($head?->logo_path && file_exists(storage_path('app/public/' . $head->logo_path)))
            <img class="logo" src="{{ storage_path('app/public/' . $head->logo_path) }}" alt="Official logo">
        @endif
        <div class="institution">{{ $head?->institution_name ?? 'Carder Management' }}</div>
        <div class="ministry">{{ $head?->ministry_name ?? 'Ministry of Health - Sri Lanka' }}</div>
        @if ($head?->department_name)
            <div>{{ $head->department_name }}</div>
        @endif
        @if ($head?->address_line_1 || $head?->address_line_2)
            <div class="small">{{ collect([$head?->address_line_1, $head?->address_line_2])->filter()->implode(', ') }}
            </div>
        @endif
        @if ($head?->telephone || $head?->email || $head?->website)
            <div class="small">
                {{ collect([$head?->telephone ? 'Tel: ' . $head->telephone : null, $head?->email, $head?->website])->filter()->implode(' | ') }}
            </div>
        @endif
        @if ($head?->header_note)
            <div class="small">{{ $head->header_note }}</div>
        @endif
    </div>

    <table class="meta">
        <tr>
            <td><strong>Our Ref:</strong> {{ $letter->reference_no ?: '—' }}</td>
            <td><strong>Date:</strong>
                {{ optional($letter->issued_at ?? $letter->approved_at)->format('d M Y') ?: now()->format('d M Y') }}
            </td>
        </tr>
    </table>

    <div class="title">{{ $letter->title }}</div>
    <div class="body">{{ $letter->live_content }}</div>

    <div class="signature">
        @if ($signatureDataUri)
            <img src="{{ $signatureDataUri }}" alt="Registered e-signature"><br>
        @endif
        <strong>{{ $letter->approvedBy?->name ?? 'Authorised Approver' }}</strong><br>
        {{ $head?->signatory_designation ?? 'Authorised Officer' }}<br>
        <span class="small">Electronically approved {{ optional($letter->approved_at)->format('d M Y H:i') }}</span>
    </div>

    <div class="proof">
        <strong>Official record integrity:</strong><br>
        SHA-256: {{ $letter->approved_content_hash ?: $letter->contentHash() }}<br>
        Workflow status: {{ strtoupper($letter->workflow_status) }} · Classification:
        {{ strtoupper($letter->document_classification) }}
        @if (!$letter->e_signature_id)
            <br><strong>Note:</strong> No registered signature image was attached at approval time. Approval identity
            and integrity evidence remain recorded in the system audit trail.
        @endif
    </div>

    @if ($head?->footer_note)
        <div class="footer">{{ $head->footer_note }}</div>
    @else
        <div class="footer classification">OFFICIAL COPY · {{ strtoupper($letter->document_classification) }}</div>
    @endif
</body>

</html>
