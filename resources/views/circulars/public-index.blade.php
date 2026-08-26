<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Memos &amp; Circulars — HIMS PARIKSHA</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0B1418; color: #E8EEF0; min-height: 100vh; padding: 32px 20px;
        }
        .wrap { max-width: 900px; margin: 0 auto; }
        header { margin-bottom: 24px; }
        h1 { font-size: 26px; margin: 0 0 6px; }
        .subtitle { font-size: 13.5px; color: #9FB4B8; }
        .search-card {
            background: #12232A; border-radius: 14px; padding: 20px; margin-bottom: 20px;
        }
        .search-grid {
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end;
        }
        @media (max-width: 800px) { .search-grid { grid-template-columns: 1fr 1fr; } }
        label { display: block; font-size: 11px; color: #6B8489; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; }
        input, select {
            width: 100%; background: #0B1418; border: 1px solid #23393F; color: #E8EEF0;
            border-radius: 8px; padding: 9px 11px; font-size: 13px; font-family: inherit;
        }
        input:focus, select:focus { outline: none; border-color: #02C39A; }
        .search-btn {
            background: #02C39A; color: #06201A; border: none; border-radius: 8px;
            padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
        }
        .search-btn:hover { background: #02A886; }
        .list { display: flex; flex-direction: column; gap: 10px; }
        .item {
            background: #12232A; border-radius: 12px; padding: 16px 20px; display: flex;
            align-items: center; justify-content: space-between; gap: 16px; text-decoration: none; color: inherit;
            transition: background .15s;
        }
        .item:hover { background: #16292F; }
        .item-badge {
            display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px;
            font-weight: 600; text-transform: uppercase; letter-spacing: .4px;
            background: rgba(2,192,154,.15); color: #02C39A; margin-bottom: 6px;
        }
        .item-title { font-size: 15px; font-weight: 600; margin: 0 0 4px; }
        .item-desc { font-size: 12.5px; color: #9FB4B8; margin: 0; }
        .item-meta { font-size: 11.5px; color: #52696D; text-align: right; white-space: nowrap; }
        .empty { text-align: center; padding: 60px 20px; color: #6B8489; }
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
        .pagination a, .pagination span {
            padding: 7px 12px; border-radius: 6px; font-size: 12.5px; text-decoration: none;
            color: #9FB4B8; background: #12232A;
        }
        .pagination .active { background: #02C39A; color: #06201A; font-weight: 600; }
        .footer { text-align: center; font-size: 11px; color: #52696D; margin-top: 36px; }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <h1>📢 Memos &amp; Circulars</h1>
            <p class="subtitle">Teaching Hospital Peradeniya — public read-only listing, no login required</p>
        </header>

        <div class="search-card">
            <form method="GET" class="search-grid">
                <div>
                    <label>Search</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Title or description…">
                </div>
                <div>
                    <label>Category</label>
                    <select name="category">
                        <option value="">All</option>
                        @foreach(\App\Models\Circular::CATEGORY_LABELS as $key => $label)
                            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div>
                    <label>To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div>
                    <label>Sort</label>
                    <select name="sort">
                        <option value="newest"      {{ $sort === 'newest' ? 'selected' : '' }}>Newest first</option>
                        <option value="oldest"      {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                        <option value="most_viewed" {{ $sort === 'most_viewed' ? 'selected' : '' }}>Most viewed</option>
                    </select>
                </div>
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>

        <div class="list">
            @forelse($circulars as $c)
            <a href="{{ route('circulars.public-show', $c->share_token) }}" class="item">
                <div>
                    <span class="item-badge">{{ \App\Models\Circular::CATEGORY_LABELS[$c->category] ?? $c->category }}</span>
                    <div class="item-title">{{ $c->title }}</div>
                    @if($c->description)
                        <p class="item-desc">{{ \Illuminate\Support\Str::limit($c->description, 120) }}</p>
                    @endif
                </div>
                <div class="item-meta">
                    {{ $c->created_at->format('d M Y') }}<br>
                    {{ number_format($c->view_count) }} view{{ $c->view_count === 1 ? '' : 's' }}
                </div>
            </a>
            @empty
            <div class="empty">
                No circulars match your search.
            </div>
            @endforelse
        </div>

        @if($circulars->hasPages())
        <div class="pagination">
            {{ $circulars->onEachSide(1)->links('vendor.pagination.material-public') }}
        </div>
        @endif

        <div class="footer">
            Teaching Hospital Peradeniya · HIMS PARIKSHA
        </div>
    </div>
</body>
</html>
