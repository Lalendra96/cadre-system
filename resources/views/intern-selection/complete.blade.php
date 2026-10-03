<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Selection Complete — {{ $batch->name }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0B1418;
            color: #E8EEF0;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #12232A;
            border-radius: 18px;
            padding: 32px;
            max-width: 420px;
            text-align: center;
        }

        .check {
            font-size: 40px;
            margin-bottom: 12px;
        }

        h1 {
            font-size: 18px;
            color: #02C39A;
            margin: 0 0 20px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #2C4046;
            font-size: 13px;
        }

        .row:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="check">✅</div>
        <h1>{{ $intern->name }} — Selection Complete</h1>
        <div class="row"><span>1st
                Appointment</span><strong>{{ $intern->firstAppointment()?->rotationUnit->name }}</strong></div>
        <div class="row"><span>2nd
                Appointment</span><strong>{{ $intern->secondAppointment()?->rotationUnit->name }}</strong></div>
    </div>
</body>

</html>
