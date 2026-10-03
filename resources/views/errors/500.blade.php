<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error 500</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f5f7fa;margin:0;color:#243447}
        .wrap{max-width:720px;margin:10vh auto;padding:24px}
        .card{background:#fff;border:1px solid #dbe3ea;border-radius:12px;padding:36px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,.06)}
        h1{margin-top:0;color:#2f5f7f} p{line-height:1.6;color:#5d6b78}
        a{display:inline-block;margin-top:12px;padding:10px 18px;border-radius:8px;background:#4682b4;color:#fff;text-decoration:none}
    </style>
</head>
<body><div class="wrap"><div class="card">
<h1>Error 500</h1>
<p>{{ $message ?? 'The request could not be completed safely. Please try again or contact the system administrator.' }}</p>
<a href="{{ url('/') }}">Return to application</a>
</div></div></body></html>
