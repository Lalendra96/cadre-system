<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111
        }

        h1 {
            text-align: center;
            font-size: 18px
        }

        .meta {
            margin: 16px 0;
            width: 100%;
            border-collapse: collapse
        }

        .meta td {
            padding: 5px;
            border-bottom: 1px solid #ddd
        }

        .box {
            border: 1px solid #999;
            padding: 12px;
            margin: 12px 0
        }

        .grid {
            width: 100%;
            border-collapse: collapse
        }

        .grid th,
        .grid td {
            border: 1px solid #aaa;
            padding: 6px;
            text-align: left
        }

        .sign {
            margin-top: 42px;
            width: 100%;
            border-collapse: collapse
        }

        .sign td {
            width: 33%;
            padding: 8px;
            text-align: center;
            vertical-align: bottom
        }

        .signature-img {
            max-width: 120px;
            max-height: 55px;
            margin: 0 auto 6px
        }

        .sigline {
            border-top: 1px solid #333;
            padding-top: 5px
        }

        .small {
            font-size: 9px;
            color: #444
        }

        .hash {
            word-break: break-all
        }
    </style>
</head>

<body>
    <h1>FORMAL RESPONSIBILITY HANDOVER CERTIFICATE</h1>
    <table class="meta">
        <tr>
            <td><strong>Reference</strong></td>
            <td>{{ $row->handover_no }}</td>
            <td><strong>Effective</strong></td>
            <td>{{ $row->effective_on }}</td>
        </tr>
        <tr>
            <td><strong>Outgoing officer</strong></td>
            <td>{{ $row->from_name }}</td>
            <td><strong>Incoming officer</strong></td>
            <td>{{ $row->to_name }}</td>
        </tr>
        <tr>
            <td><strong>Supervisor</strong></td>
            <td>{{ $row->supervisor_name ?: 'Not assigned' }}</td>
            <td><strong>Status</strong></td>
            <td>{{ ucfirst($row->status) }}</td>
        </tr>
    </table>
    @php
        $snapshotLabels = [
            'employee_files' => 'Employee files',
            'pending_promotions' => 'Pending promotions',
            'increment_cases' => 'Increment cases',
            'retirement_cases' => 'Retirement cases',
            'data_quality_issues' => 'Data-quality issues',
            'open_escalations' => 'Open escalations',
            'pending_decisions' => 'Pending administrative decisions',
            'pending_corrections' => 'Pending corrections',
            'acting_appointments' => 'Active acting appointments',
            'service_letters' => 'Pending service letters',
            'regulatory_actions' => 'Regulatory actions',
        ];
    @endphp
    <div class="box">
        <strong>Point-in-time workload snapshot</strong>
        <table class="grid">
            <tbody>
                @foreach ($snapshotLabels as $key => $label)
                    <tr>
                        <th>{{ $label }}</th>
                        <td>{{ $snapshot[$key] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="small">Snapshot captured: {{ $snapshot['captured_at'] ?? '—' }}</p>
    </div>
    <div class="box"><strong>Notes / outstanding actions</strong>
        <p>{{ $row->notes ?: 'No additional notes recorded.' }}</p>
    </div>
    <table class="sign">
        <tr>
            <td>
                @if ($signatures['from'] ?? null)
                    <img class="signature-img" src="{{ $signatures['from'] }}">
                @endif
                <div class="sigline">
                    Outgoing
                    Officer<br>{{ $row->from_name }}<br><small>{{ $row->handed_over_at ? 'Digitally acknowledged ' . $row->handed_over_at : 'Not yet acknowledged' }}</small>
                </div>
            </td>
            <td>
                @if ($signatures['to'] ?? null)
                    <img class="signature-img" src="{{ $signatures['to'] }}">
                @endif
                <div class="sigline">
                    Incoming
                    Officer<br>{{ $row->to_name }}<br><small>{{ $row->accepted_at ? 'Digitally accepted ' . $row->accepted_at : 'Not yet accepted' }}</small>
                </div>
            </td>
            <td>
                @if ($signatures['supervisor'] ?? null)
                    <img class="signature-img" src="{{ $signatures['supervisor'] }}">
                @endif
                <div class="sigline">Supervisor
                    Verification<br>{{ $row->supervisor_name ?: '—' }}<br><small>{{ $row->verified_at ? 'Verified ' . $row->verified_at : 'Not yet verified' }}</small>
                </div>
            </td>
        </tr>
    </table>
    @if ($row->content_hash)
        <p class="small hash"><strong>Evidence hash:</strong> {{ $row->content_hash }}</p>
    @endif
    <p class="small">This certificate is generated from a point-in-time system workload snapshot. Authentication
        timestamps and registered e-signature assets, when available, are retained as supporting evidence.</p>
</body>

</html>
