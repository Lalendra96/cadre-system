/** Local Chart.js presentation controls. Values, series colors and scales are preserved. */
(() => {
    "use strict";
    if (!window.Chart) return;
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)");
    Chart.defaults.animation.duration = reduced.matches ? 0 : 700;
    Chart.defaults.animation.easing = "easeOutQuart";
    Chart.defaults.elements.bar.borderRadius = 5;
    Chart.defaults.elements.bar.hoverBorderWidth = 2;
    Chart.defaults.elements.point.hoverRadius = 7;
    Chart.defaults.elements.line.borderWidth = 2.5;
    const format = new Intl.NumberFormat(undefined, {
        maximumFractionDigits: 2,
    });
    const valueOf = (chart, raw) => {
        if (raw === null || raw === undefined) return null;
        const value =
            typeof raw === "object"
                ? raw[chart.options.indexAxis === "y" ? "x" : "y"]
                : raw;
        if (
            value === "" ||
            value === null ||
            value === undefined ||
            typeof value === "boolean"
        )
            return null;
        return Number.isFinite(Number(value)) ? Number(value) : null;
    };
    function refreshTable(chart) {
        const ui = chart.$carderTools;
        if (!ui || !ui.details.open) return;
        const table = document.createElement("table");
        const head = table.createTHead().insertRow();
        [
            "Category",
            ...chart.data.datasets.map((d) => d.label || "Series"),
        ].forEach((label) => {
            const cell = document.createElement("th");
            cell.scope = "col";
            cell.textContent = label;
            head.append(cell);
        });
        const rows =
            chart.data.labels ||
            chart.data.datasets[0]?.data.map((_, i) => `Point ${i + 1}`) ||
            [];
        const body = table.createTBody();
        rows.slice(0, 100).forEach((label, index) => {
            const row = body.insertRow();
            row.insertCell().textContent = String(label);
            chart.data.datasets.forEach((dataset) => {
                const value = valueOf(chart, dataset.data[index]);
                row.insertCell().textContent =
                    value === null ? "—" : format.format(value);
            });
        });
        ui.table.replaceChildren(table);
        if (rows.length > 100) {
            const note = document.createElement("p");
            note.textContent =
                "Showing the first 100 categories. Use the section report/export for the complete dataset.";
            ui.table.append(note);
        }
    }
    Chart.register({
        id: "carderChartTools",
        afterInit(chart) {
            const canvas = chart.canvas;
            const panel = canvas.closest(
                ".dashboard-widget,.workforce-panel,.cadre-dashboard-panel,.governance-card,.md-card,.chart-card",
            );
            const title =
                canvas.getAttribute("aria-label") ||
                panel
                    ?.querySelector(
                        "h2,h3,.panel-title,.dashboard-widget__title",
                    )
                    ?.textContent.trim() ||
                "Chart";
            canvas.setAttribute("role", "img");
            canvas.setAttribute("aria-label", title);
            const tools = document.createElement("div");
            tools.className = "chart-tools";
            const actions = document.createElement("div");
            actions.className = "chart-tools__actions";
            const highlight = document.createElement("button");
            highlight.type = "button";
            highlight.textContent = "Highlight largest value";
            const clear = document.createElement("button");
            clear.type = "button";
            clear.textContent = "Clear highlight";
            const status = document.createElement("span");
            status.setAttribute("role", "status");
            status.setAttribute("aria-live", "polite");
            const details = document.createElement("details");
            const summary = document.createElement("summary");
            summary.textContent = "Chart guide & data table";
            const guide = document.createElement("p");
            guide.textContent =
                "Hover over a mark for its value. Select a legend item to show or hide a series. Highlight searches visible series only. Compare values using this section’s selected dates and filters.";
            const table = document.createElement("div");
            table.className = "chart-data-scroll";
            actions.append(highlight, clear, status);
            details.append(summary, guide, table);
            tools.append(actions, details);
            const host = canvas.parentElement;
            if (host && !["BODY", "MAIN"].includes(host.tagName))
                host.insertAdjacentElement("afterend", tools);
            else canvas.insertAdjacentElement("afterend", tools);
            chart.$carderTools = { tools, details, table, status };
            details.addEventListener("toggle", () => refreshTable(chart));
            highlight.addEventListener("click", () => {
                let best = null;
                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    if (!chart.isDatasetVisible(datasetIndex)) return;
                    dataset.data.forEach((raw, index) => {
                        if (!chart.getDataVisibility(index)) return;
                        const value = valueOf(chart, raw);
                        const element =
                            chart.getDatasetMeta(datasetIndex).data[index];
                        if (
                            value !== null &&
                            element &&
                            !element.skip &&
                            (!best || value > best.value)
                        )
                            best = { datasetIndex, index, value, element };
                    });
                });
                if (!best) {
                    status.textContent =
                        "No visible numeric values to highlight.";
                    return;
                }
                const active = [
                    { datasetIndex: best.datasetIndex, index: best.index },
                ];
                chart.setActiveElements(active);
                chart.tooltip?.setActiveElements(
                    active,
                    best.element.tooltipPosition(),
                );
                canvas.classList.add("chart-emphasis");
                chart.update("none");
                status.textContent = `${chart.data.labels?.[best.index] ?? "Point " + (best.index + 1)} — ${chart.data.datasets[best.datasetIndex].label || "Series"}: ${format.format(best.value)}`;
            });
            clear.addEventListener("click", () => {
                chart.setActiveElements([]);
                chart.tooltip?.setActiveElements([], { x: 0, y: 0 });
                canvas.classList.remove("chart-emphasis");
                status.textContent = "Highlight cleared.";
                chart.update("none");
            });
        },
        beforeUpdate(chart) {
            if (reduced.matches) chart.options.animation = false;
        },
        afterUpdate(chart) {
            refreshTable(chart);
        },
        afterDestroy(chart) {
            chart.$carderTools?.tools.remove();
        },
    });
    reduced.addEventListener("change", () =>
        Object.values(Chart.instances).forEach((chart) => {
            chart.options.animation = reduced.matches
                ? false
                : { duration: 700, easing: "easeOutQuart" };
            chart.update("none");
        }),
    );
})();
