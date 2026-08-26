<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $circular->title }} — HIMS PARIKSHA</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0B1418; color: #E8EEF0; min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 24px;
        }
        .card {
            background: #12232A; border-radius: 16px; padding: 40px; max-width: 480px; width: 100%;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
        }
        .badge {
            display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 11px;
            font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
            background: rgba(2,192,154,.15); color: #02C39A; margin-bottom: 16px;
        }
        h1 { font-size: 22px; margin: 0 0 8px; line-height: 1.3; }
        .desc { font-size: 14px; color: #9FB4B8; line-height: 1.6; margin-bottom: 24px; }
        .meta { font-size: 12.5px; color: #6B8489; margin-bottom: 28px; display: flex; flex-direction: column; gap: 4px; }
        .download-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background: #02C39A; color: #06201A; text-decoration: none; font-weight: 600;
            padding: 14px 20px; border-radius: 10px; font-size: 14px;
        }
        .download-btn:hover { background: #02A886; }
        .footer { text-align: center; font-size: 11px; color: #52696D; margin-top: 28px; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">{{ \App\Models\Circular::CATEGORY_LABELS[$circular->category] ?? $circular->category }}</span>
        <h1>{{ $circular->title }}</h1>
        @if($circular->description)
            <p class="desc">{{ $circular->description }}</p>
        @endif
        <div class="meta">
            <span>📄 {{ $circular->original_filename }} ({{ $circular->file_size_for_humans }})</span>
            <span>🗓 Uploaded {{ $circular->created_at->format('d M Y') }}</span>
        </div>
        <a href="{{ route('circulars.public-download', $circular->share_token) }}" class="download-btn">
            ⬇ Download Document
        </a>
        <div class="footer">
            Teaching Hospital Peradeniya · HIMS PARIKSHA
        </div>
    </div>
</body>
</html>
