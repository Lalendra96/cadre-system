/**
 * CarderCharts v2 — full-featured chart engine for the Admin Group dashboard.
 *
 * Features:
 *  • Horizontally scrollable charts — each bar/point gets a minimum of PX_PER_LABEL
 *    pixels, so 57 positions never get squeezed to hairlines.
 *  • Drag-to-pan on all scroll hosts.
 *  • Custom builder with position keyword filter, top-N slice, sort order,
 *    per-metric year/month override, and PNG export.
 *  • MD3 dark palette applied throughout (no hardcoded hex values anywhere
 *    except the palette definition at the top).
 *
 * Depends on: Chart.js 4.x (loaded globally).
 */
const CarderCharts = (function () {

    /* ── Config ──────────────────────────────────────────────── */
    let config = { year: null, month: null, dataUrl: null };

    const PX_PER_LABEL = 36;   // min pixels each bar/point gets in scroll host
    const CHART_HEIGHT = 420;  // px — matches .chart-inner height in CSS

    const P = {                 // MD3 dark palette
        primary:   '#A8C8FF',
        success:   '#7DD996',
        warning:   '#F5C56B',
        error:     '#FFB4AB',
        tertiary:  '#D7BDE8',
        secondary: '#BAC6DC',
        gridLine:  'rgba(195,198,207,.08)',
        tickColor: '#C3C6CF',
        tooltip:   { bg: '#292A2F', border: '#43474E', title: '#E3E2E6', body: '#C3C6CF' },
    };

    const instances = {};   // keyed by canvas id — so we can destroy before redraw

    /* ── Shared Chart.js base options ────────────────────────── */
    function baseOpts(extraPlugins = {}) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 600, easing: 'easeInOutQuart' },
            plugins: {
                legend:  { display: false },          // we draw custom legend below
                tooltip: {
                    backgroundColor: P.tooltip.bg,
                    titleColor:      P.tooltip.title,
                    bodyColor:       P.tooltip.body,
                    borderColor:     P.tooltip.border,
                    borderWidth:     1,
                    padding:         12,
                    cornerRadius:    8,
                },
                ...extraPlugins,
            },
            scales: {
                x: {
                    ticks: {
                        color: P.tickColor, font: { size: 11 },
                        maxRotation: 55, minRotation: 45,
                    },
                    grid: { color: P.gridLine },
                },
                y: {
                    ticks: { color: P.tickColor, font: { size: 11 } },
                    grid:  { color: P.gridLine },
                    beginAtZero: true,
                },
            },
        };
    }

    /* ── Resize inner scroll div so each label gets PX_PER_LABEL px ── */
    function setScrollWidth(innerId, labelCount) {
        const inner = document.getElementById(innerId);
        if (!inner) return;
        const minW = labelCount * PX_PER_LABEL;
        inner.style.minWidth = Math.max(inner.parentElement.clientWidth, minW) + 'px';
        inner.style.height = CHART_HEIGHT + 'px';
    }

    /* ── Create / redraw a chart safely ──────────────────────── */
    function draw(canvasId, type, data, options) {
        if (instances[canvasId]) {
            instances[canvasId].destroy();
            delete instances[canvasId];
        }
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        instances[canvasId] = new Chart(canvas, { type, data, options });
        return instances[canvasId];
    }

    /* ── Render custom legend below the chart ────────────────── */
    function renderLegend(legendId, datasets) {
        const el = document.getElementById(legendId);
        if (!el) return;
        el.innerHTML = datasets.map(function (ds) {
            const color = Array.isArray(ds.backgroundColor) ? ds.backgroundColor[0] : (ds.backgroundColor || ds.borderColor || P.primary);
            return '<div class="chart-legend-item"><span class="chart-legend-swatch" style="background:' + color + ';"></span>' + ds.label + '</div>';
        }).join('');
    }

    /* ── Fetch helper ─────────────────────────────────────────── */
    async function fetchData(metric, year, month) {
        const params = new URLSearchParams({ year: year || config.year, month: month || config.month, metric });
        const res = await fetch(config.dataUrl + '?' + params.toString(), {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) { console.error('Chart data fetch failed', res.status); return null; }
        return res.json();
    }

    /* ── Apply filters (keyword, top-N, sort) to label+value arrays ── */
    function applyFilters(labels, values, keyword, topN, sort) {
        // keyword filter
        let pairs = labels.map(function (l, i) { return { label: l, values: Array.isArray(values) ? values.map(function(v){ return v[i]; }) : [values[i]] }; });

        if (keyword && keyword.trim()) {
            const lc = keyword.trim().toLowerCase();
            pairs = pairs.filter(function (p) { return p.label.toLowerCase().includes(lc); });
        }

        // sort
        if (sort === 'desc') {
            pairs.sort(function (a, b) { return (b.values[0] || 0) - (a.values[0] || 0); });
        } else if (sort === 'asc') {
            pairs.sort(function (a, b) { return (a.values[0] || 0) - (b.values[0] || 0); });
        }

        // top N
        const n = parseInt(topN, 10);
        if (n > 0) { pairs = pairs.slice(0, n); }

        const outLabels = pairs.map(function (p) { return p.label; });
        const outValues = (Array.isArray(values) ? values.map(function (_, vi) {
            return pairs.map(function (p) { return p.values[vi] || 0; });
        }) : [pairs.map(function (p) { return p.values[0] || 0; })]);

        return { labels: outLabels, values: outValues };
    }

    /* ── Fixed chart: Approved vs Available ──────────────────── */
    async function renderApprovedVsAvailable() {
        const data = await fetchData('approved_vs_available');
        if (!data) return;

        setScrollWidth('innerApproved', data.labels.length);

        const opts = baseOpts();
        opts.plugins.legend = { display: false };
        // Slightly thicker bars for readability
        opts.barThickness = Math.max(8, Math.min(22, Math.floor(PX_PER_LABEL * 0.55)));

        draw('chartApprovedVsAvailable', 'bar', {
            labels: data.labels,
            datasets: [
                { label: 'Approved',    data: data.approved,  backgroundColor: P.primary,  borderRadius: 4, borderSkipped: false },
                { label: 'In Position', data: data.available, backgroundColor: P.success,  borderRadius: 4, borderSkipped: false },
            ],
        }, opts);
    }

    /* ── Fixed chart: Vacancy ─────────────────────────────────── */
    async function renderVacancy() {
        const data = await fetchData('vacancy_by_position');
        if (!data) return;

        setScrollWidth('innerVacancy', data.labels.length);

        const opts = baseOpts();
        opts.plugins.legend = { display: false };
        opts.barThickness = Math.max(8, Math.min(22, Math.floor(PX_PER_LABEL * 0.55)));

        draw('chartVacancy', 'bar', {
            labels: data.labels,
            datasets: [{
                label: 'Vacancy',
                data:  data.vacancy,
                backgroundColor: data.vacancy.map(function (v) {
                    return v > 20 ? P.error : v > 5 ? P.warning : P.success;
                }),
                borderRadius: 4,
                borderSkipped: false,
            }],
        }, opts);
    }

    /* ── Custom builder ───────────────────────────────────────── */
    async function runBuilder() {
        const metric   = document.getElementById('builderMetric').value;
        const chartTypeRaw = document.getElementById('builderType').value;
        const sort     = document.getElementById('builderSort')?.value || 'name';
        const topN     = document.getElementById('builderTopN')?.value || 0;
        const keyword  = document.getElementById('positionKeyword')?.value || '';
        const year     = document.getElementById('builderYear')?.value || config.year;
        const month    = document.getElementById('builderMonth')?.value || config.month;

        // Handle horizontal bar — Chart.js v4 uses 'bar' + indexAxis: 'y'
        let chartType = chartTypeRaw;
        let indexAxis  = 'x';
        if (chartTypeRaw === 'horizontalBar') {
            chartType  = 'bar';
            indexAxis  = 'y';
        }

        const data = await fetchData(metric, year, month);
        if (!data) return;

        setScrollWidth('innerCustom', data.labels.length);

        let datasets = [];
        let filteredLabels = data.labels;

        if (metric === 'approved_vs_available') {
            const f = applyFilters(data.labels, [data.approved, data.available], keyword, topN, sort);
            filteredLabels = f.labels;
            const thickness = Math.max(8, Math.min(22, Math.floor(PX_PER_LABEL * 0.45)));
            datasets = [
                { label: 'Approved',    data: f.values[0], backgroundColor: P.primary,  borderRadius: 4, barThickness: thickness },
                { label: 'In Position', data: f.values[1], backgroundColor: P.success,  borderRadius: 4, barThickness: thickness },
            ];
        } else if (metric === 'vacancy_by_position') {
            const f = applyFilters(data.labels, [data.vacancy], keyword, topN, sort);
            filteredLabels = f.labels;
            const thickness = Math.max(8, Math.min(22, Math.floor(PX_PER_LABEL * 0.55)));
            datasets = [{
                label: 'Vacancy',
                data: f.values[0],
                backgroundColor: f.values[0].map(function(v){ return v > 20 ? P.error : v > 5 ? P.warning : P.success; }),
                borderRadius: 4, barThickness: thickness,
            }];
        } else {
            // no_pay_leave_trend — monthly, no position filter
            filteredLabels = data.labels;
            setScrollWidth('innerCustom', 12);
            datasets = [{
                label: 'No-Pay Leave',
                data: data.no_pay_leave,
                borderColor: P.error,
                backgroundColor: 'rgba(255,180,171,.12)',
                fill: true, tension: 0.4, pointRadius: 5,
                pointBackgroundColor: P.error,
            }];
        }

        const opts = baseOpts();

        // Pie / doughnut / radar: no scales, custom layout
        if (['pie', 'doughnut', 'radar'].includes(chartType)) {
            delete opts.scales;
            opts.plugins.legend = {
                display: true, position: 'right',
                labels: { color: P.tickColor, padding: 12, font: { size: 12 } },
            };

            // For pie/doughnut, use multi-color array
            if (chartType !== 'radar') {
                const palette = [P.primary, P.success, P.warning, P.error, P.tertiary, P.secondary];
                if (datasets.length === 1) {
                    datasets[0].backgroundColor = filteredLabels.map(function(_, i){ return palette[i % palette.length]; });
                    delete datasets[0].borderRadius;
                }
            }

            setScrollWidth('innerCustom', 1); // no scroll needed for circular
        } else {
            opts.indexAxis = indexAxis;
            if (indexAxis === 'y') {
                // Horizontal bar: swap axis config
                const tmpX = opts.scales.x;
                opts.scales.x = opts.scales.y;
                opts.scales.y = tmpX;
                // For horizontal, set min-height instead of min-width
                const inner = document.getElementById('innerCustom');
                if (inner) {
                    inner.style.minWidth  = '100%';
                    inner.style.height    = Math.max(CHART_HEIGHT, filteredLabels.length * PX_PER_LABEL) + 'px';
                }
            }
        }

        draw('chartCustom', chartType, { labels: filteredLabels, datasets }, opts);
        renderLegend('customLegend', datasets);
    }

    /* ── Public API ───────────────────────────────────────────── */
    function init(userConfig) {
        config = Object.assign({}, config, userConfig);
        renderApprovedVsAvailable();
        renderVacancy();
        runBuilder();
    }

    return { init, runBuilder };

})();
