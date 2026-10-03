<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Intern Rotation Selection — {{ $batch->name }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/selectize/selectize.css') }}">
    <script src="{{ asset('vendor/selectize/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/selectize/selectize.min.js') }}"></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0B1418;
            color: #E8EEF0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .card {
            background: #12232A;
            border-radius: 18px;
            padding: 32px;
            max-width: 420px;
            width: 100%;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 6px;
            color: #02C39A;
        }

        p.sub {
            color: #9FB4B8;
            font-size: 13px;
            margin: 0 0 24px;
        }

        label {
            display: block;
            font-size: 13px;
            color: #9FB4B8;
            margin-bottom: 6px;
        }

        select,
        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 18px;
            border-radius: 8px;
            background: #0B1418;
            border: 1px solid #2C4046;
            color: #E8EEF0;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 12px;
            border-radius: 999px;
            background: #02C39A;
            color: #04211B;
            font-weight: 600;
            border: none;
            font-size: 14px;
            cursor: pointer;
        }

        .error {
            background: #3a1b1b;
            color: #f0a99e;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .selectize-control.single .selectize-input {
            background: #0B1418;
            border-color: #2C4046;
            color: #E8EEF0;
        }

        .selectize-dropdown {
            background: #0B1418;
            border-color: #2C4046;
            color: #E8EEF0;
        }

        .selectize-dropdown .option.active {
            background: #1C2E33;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/md3-dark.css') }}?v=offline-20260924">
    <link rel="stylesheet" href="{{ asset('css/carder-professional.css') }}?v=offline-20260924">
    <link rel="stylesheet" href="{{ asset('css/interface-consistency.css') }}?v=20260924">
    <link rel="stylesheet" href="{{ asset('css/public-interface.css') }}?v=20260924">
    <script src="{{ asset('js/form-validation.js') }}?v=20260924" defer></script>
</head>

<body class="public-workspace">
    <div class="card">
        <h1>{{ $batch->name }}</h1>
        <p class="sub">Select your name and confirm the last 4 digits of your NIC to continue.</p>

        @if (session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('intern-selection.verify', $batch) }}">
            @csrf
            <label for="internSelect">Your Name</label>
            <select id="internSelect" name="intern_id" required>
                <option value="">Select your name…</option>
                @foreach ($interns as $intern)
                    <option value="{{ $intern->id }}" {{ old('intern_id') == $intern->id ? 'selected' : '' }}>
                        {{ $intern->name }}</option>
                @endforeach
            </select>

            <label for="nicLastFour">Last 4 digits of your NIC</label>
            <input type="text" id="nicLastFour" name="nic_last_four" maxlength="4" pattern="\d{4}"
                inputmode="numeric" placeholder="e.g. 5678" required>

            <button type="submit">Continue →</button>
        </form>
    </div>

    <script>
        $('#internSelect').selectize({
            create: false,
            sortField: 'text',
            placeholder: 'Type to search your name…'
        });
    </script>
</body>

</html>
