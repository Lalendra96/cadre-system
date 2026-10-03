<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $carPass->reference_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            color: #102a43;
        }

        .pass {
            width: 760px;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            overflow: hidden;
        }

        .template {
            width: 100%;
            max-height: 420px;
            object-fit: contain;
            background: #f8fafc;
        }

        .details {
            padding: 22px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 28px;
        }

        .label {
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
        }

        .value {
            font-size: 18px;
            font-weight: 700;
            margin-top: 3px;
        }

        .footer {
            padding: 14px 22px;
            background: #f8fafc;
            font-size: 12px;
        }

        .revoked {
            color: #b91c1c;
            font-size: 28px;
            font-weight: 800;
            text-align: center;
            padding: 12px;
            border: 3px solid #b91c1c;
            margin: 15px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="text-align:center;margin-bottom:12px;"><button onclick="window.print()">Print</button>
    </div>
    <div class="pass">
        <img class="template" src="{{ route('car-passes.snapshot-image', $carPass) }}" alt="Car Pass format">
        @if ($carPass->status === 'revoked')
            <div class="revoked">REVOKED</div>
        @endif
        <div class="details">
            <div>
                <div class="label">Employee</div>
                <div class="value">{{ $carPass->employee_name_snapshot }}</div>
            </div>
            <div>
                <div class="label">Reference</div>
                <div class="value">{{ $carPass->reference_no }}</div>
            </div>
            <div>
                <div class="label">Post</div>
                <div class="value">{{ $carPass->position_snapshot ?: '—' }}</div>
            </div>
            <div>
                <div class="label">Unit</div>
                <div class="value">{{ $carPass->unit_snapshot ?: '—' }}</div>
            </div>
            <div>
                <div class="label">Vehicle Registration</div>
                <div class="value">{{ $carPass->vehicle_registration_no }}</div>
            </div>
            <div>
                <div class="label">Vehicle Type</div>
                <div class="value">{{ ucwords(str_replace('_', ' ', $carPass->vehicle_type)) }}</div>
            </div>
            <div>
                <div class="label">Valid From</div>
                <div class="value">{{ $carPass->valid_from?->format('d M Y') }}</div>
            </div>
            <div>
                <div class="label">Valid To</div>
                <div class="value">{{ $carPass->valid_to?->format('d M Y') }}</div>
            </div>
        </div>
        <div class="footer">
            Approved by {{ $carPass->reviewer?->name ?: '—' }}
            @if ($carPass->approved_at)
                on {{ $carPass->approved_at->format('d M Y H:i') }}
            @endif
            · Integrity: {{ $carPass->content_hash ?: '—' }}
            @if ($carPass->status === 'revoked')
                · Revoked: {{ $carPass->revocation_reason }}
            @endif
        </div>
    </div>
</body>

</html>
