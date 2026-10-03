<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Pick Your Rotation — {{ $batch->name }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0B1418;
            color: #E8EEF0;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
        }

        .wrap {
            max-width: 560px;
            margin: 0 auto;
        }

        h1 {
            font-size: 20px;
            color: #02C39A;
            margin: 0 0 4px;
        }

        p.sub {
            color: #9FB4B8;
            font-size: 13px;
            margin: 0 0 24px;
        }

        .card {
            background: #12232A;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .card h2 {
            font-size: 15px;
            margin: 0 0 12px;
        }

        .success {
            background: #1b3a2d;
            color: #9ef0b3;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .error {
            background: #3a1b1b;
            color: #f0a99e;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 999px;
            background: #0B1418;
            border: 1px solid #2C4046;
            font-size: 13px;
            cursor: pointer;
        }

        .chip.full {
            opacity: .4;
            cursor: not-allowed;
        }

        .chip input {
            display: none;
        }

        .chip:has(input:checked) {
            background: #02C39A;
            color: #04211B;
            border-color: #02C39A;
        }

        button {
            padding: 10px 20px;
            border-radius: 999px;
            background: #02C39A;
            color: #04211B;
            font-weight: 600;
            border: none;
            font-size: 13px;
            cursor: pointer;
            margin-top: 12px;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/md3-dark.css') }}?v=offline-20260924">
    <link rel="stylesheet" href="{{ asset('css/carder-professional.css') }}?v=offline-20260924">
    <link rel="stylesheet" href="{{ asset('css/interface-consistency.css') }}?v=20260924">
    <link rel="stylesheet" href="{{ asset('css/public-interface.css') }}?v=20260924">
    <script src="{{ asset('js/form-validation.js') }}?v=20260924" defer></script>
</head>

<body class="public-workspace">
    <div class="wrap">
        <h1>{{ $intern->name }}</h1>
        <p class="sub">{{ $batch->name }} — pick your rotation for
            {{ $needsFirst && $needsSecond ? 'each appointment' : ($needsFirst ? 'the 1st Appointment' : 'the 2nd Appointment') }}.
        </p>

        @if (session('success'))
            <div class="success">✓ {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        @foreach ([1 => $needsFirst, 2 => $needsSecond] as $appt => $needed)
            @if ($needed)
                <form method="POST" action="{{ route('intern-selection.store', $batch) }}" class="card">
                    @csrf
                    <input type="hidden" name="appointment_number" value="{{ $appt }}">
                    <h2>{{ $appt }}{{ $appt == 1 ? 'st' : 'nd' }} Appointment</h2>
                    <div class="chip-group">
                        @foreach ($rotationUnits as $unit)
                            @php
                                $remaining = $availability[$unit->id][$appt];
                            @endphp
                            <label class="chip {{ $remaining <= 0 ? 'full' : '' }}">
                                <input type="radio" name="intern_rotation_unit_id" value="{{ $unit->id }}"
                                    {{ $remaining <= 0 ? 'disabled' : '' }} required>
                                {{ $unit->name }} ({{ $remaining }} open)
                            </label>
                        @endforeach
                    </div>
                    <button type="submit">Confirm {{ $appt }}{{ $appt == 1 ? 'st' : 'nd' }}
                        Appointment</button>
                </form>
            @endif
        @endforeach
    </div>
</body>

</html>
