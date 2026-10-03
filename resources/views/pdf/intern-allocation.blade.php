<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #000;
        }

        h1 {
            font-size: 15px;
            text-align: center;
            text-decoration: underline;
            margin-bottom: 4px;
        }

        h2 {
            font-size: 12px;
            text-align: center;
            margin-top: 0;
            margin-bottom: 16px;
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            border: 1px solid #000;
            padding: 5px 8px;
            vertical-align: top;
        }

        th {
            text-align: left;
            font-size: 11px;
        }

        .unit-name {
            font-weight: bold;
        }

        .slot-row {
            padding: 1px 0;
        }
    </style>
</head>

<body>
    <h1>{{ $reportTitle }}</h1>
    <h2>{{ $reportSubtitle }}</h2>

    <table>
        <tr>
            <th style="width:25%;">Rotation Unit</th>
            <th style="width:37.5%;">1st Appointment</th>
            <th style="width:37.5%;">2nd Appointment</th>
        </tr>
        @foreach ($rotationUnits as $unit)
            <tr>
                <td class="unit-name">{{ $unit->name }}</td>
                @foreach ([1, 2] as $appt)
                    <td>
                        @forelse($grid[$unit->id][$appt] as $slotNumber => $assignment)
                            <div class="slot-row">{{ $slotNumber }}. {{ $assignment?->intern->name ?? '' }}</div>
                        @empty
                            &nbsp;
                        @endforelse
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</body>

</html>
