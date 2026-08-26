<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="300">
    <title>Cadre Statistics — HIMS PARIKSHA</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0B1418; color: #E8EEF0;
            height: 100vh; overflow: hidden;
            display: flex; flex-direction: column;
        }
        header {
            padding: 20px 40px; display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #1C2E33;
        }
        header h1 { font-size: 20px; font-weight: 600; color: #02C39A; }
        header .meta { font-size: 13px; color: #6F8A90; }
        .slides { flex: 1; position: relative; }
        .slide {
            position: absolute; inset: 0; padding: 40px 60px;
            display: flex; flex-direction: column; justify-content: center;
            opacity: 0; visibility: hidden; transition: opacity .6s ease;
        }
        .slide.active { opacity: 1; visibility: visible; }
        .slide h2 { font-size: 28px; font-weight: 600; margin-bottom: 32px; text-align: center; color: #E8EEF0; }

        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 28px; max-width: 1200px; margin: 0 auto; width: 100%; }
        .kpi-card { background: #12232A; border-radius: 18px; padding: 32px 20px; text-align: center; }
        .kpi-value { font-size: 56px; font-weight: 700; line-height: 1; margin-bottom: 10px; }
        .kpi-label { font-size: 14px; color: #9FB4B8; text-transform: uppercase; letter-spacing: 1px; }
        .kpi-value.approved { color: #7FC8E8; }
        .kpi-value.actual   { color: #02C39A; }
        .kpi-value.vacancy  { color: #E86A5C; }
        .kpi-value.fillpct  { color: #F2C94C; }

        .chart-wrap { max-width: 1100px; margin: 0 auto; width: 100%; height: 60vh; }

        .dots { display: flex; gap: 10px; justify-content: center; padding: 18px 0; }
        .dot { width: 9px; height: 9px; border-radius: 50%; background: #2C4046; transition: background .3s; }
        .dot.active { background: #02C39A; }
    </style>
</head>
<body>
    <header>
        <h1>🏥 HIMS PARIKSHA — Cadre Statistics</h1>
        <span class="meta">{{ $year }} · Updated {{ now()->format('d M Y, H:i') }}</span>
    </header>

    <div class="slides">
        {{-- Slide 1: Hero KPIs --}}
        <div class="slide active" data-slide="0">
            <h2>Hospital-Wide Cadre Position — {{ $year }}</h2>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-value approved">{{ number_format($totalApproved) }}</div>
                    <div class="kpi-label">Approved Posts</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value actual">{{ number_format($totalActual) }}</div>
                    <div class="kpi-label">In Position</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value vacancy">{{ number_format($totalVacancy) }}</div>
                    <div class="kpi-label">Vacant Posts</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value fillpct">{{ $overallFillPct }}%</div>
                    <div class="kpi-label">Overall Fill Rate</div>
                </div>
            </div>
        </div>

        {{-- Slide 2: Top vacancies (ranking -> horizontal bar) --}}
        <div class="slide" data-slide="1">
            <h2>Top 10 Positions by Vacancy</h2>
            <div class="chart-wrap"><canvas id="vacancyChart"></canvas></div>
        </div>

        {{-- Slide 3: Headcount distribution by unit type --}}
        <div class="slide" data-slide="2">
            <h2>Current Headcount by Unit Category</h2>
            <div class="chart-wrap"><canvas id="unitTypeChart"></canvas></div>
        </div>
    </div>

    <div class="dots" id="dots"></div>

    <script>
        const topVacancies = @json($topVacancies);
        const byUnitType   = @json($byUnitType);

        Chart.defaults.color = '#9FB4B8';
        Chart.defaults.borderColor = '#1C2E33';
        Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";

        new Chart(document.getElementById('vacancyChart'), {
            type: 'bar',
            data: {
                labels: topVacancies.map(r => r.label),
                datasets: [{
                    label: 'Vacant',
                    data: topVacancies.map(r => r.vacancy),
                    backgroundColor: '#E86A5C',
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 } },
                    y: { ticks: { font: { size: 13 } } }
                }
            }
        });

        new Chart(document.getElementById('unitTypeChart'), {
            type: 'bar',
            data: {
                labels: byUnitType.map(r => r.label),
                datasets: [{
                    label: 'In Position',
                    data: byUnitType.map(r => r.actual),
                    backgroundColor: '#02C39A',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { maxRotation: 45, minRotation: 45, font: { size: 11 } } }
                }
            }
        });

        // ── Slide rotation ──────────────────────────────────────────────
        const slides = document.querySelectorAll('.slide');
        const dotsWrap = document.getElementById('dots');
        slides.forEach((_, i) => {
            const d = document.createElement('div');
            d.className = 'dot' + (i === 0 ? ' active' : '');
            dotsWrap.appendChild(d);
        });
        const dots = document.querySelectorAll('.dot');

        let current = 0;
        const INTERVAL_MS = 12000;

        function showSlide(i) {
            slides.forEach(s => s.classList.remove('active'));
            dots.forEach(d => d.classList.remove('active'));
            slides[i].classList.add('active');
            dots[i].classList.add('active');
            current = i;
        }

        setInterval(() => {
            showSlide((current + 1) % slides.length);
        }, INTERVAL_MS);
    </script>
</body>
</html>
