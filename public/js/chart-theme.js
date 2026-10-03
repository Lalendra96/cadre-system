/** Keep all local Chart.js instances readable when the application theme changes. */
(() => {
    "use strict";
    if (!window.Chart) return;

    Chart.register({
        id: "carderTheme",
        beforeUpdate(chart) {
            const styles = getComputedStyle(document.documentElement);
            const token = (name, fallback) =>
                styles.getPropertyValue(name).trim() || fallback;
            const foreground = token("--md-on-surface", "#12294a");
            const muted = token("--md-on-surface-variant", "#61748d");
            const border = token("--md-outline-variant", "#d7e2ee");
            chart.options.color = foreground;
            const plugins = chart.options.plugins;
            if (plugins.legend && plugins.legend.labels)
                plugins.legend.labels.color = foreground;
            if (plugins.title) plugins.title.color = foreground;
            if (plugins.tooltip) {
                plugins.tooltip.backgroundColor = token(
                    "--md-surface-container-high",
                    "#eef4fa",
                );
                plugins.tooltip.titleColor = foreground;
                plugins.tooltip.bodyColor = foreground;
                plugins.tooltip.borderColor = border;
                plugins.tooltip.borderWidth = 1;
            }
            Object.values(chart.options.scales || {}).forEach((scale) => {
                if (scale.ticks) scale.ticks.color = muted;
                if (scale.grid) scale.grid.color = border;
                if (scale.title) scale.title.color = foreground;
                if (scale.pointLabels) scale.pointLabels.color = muted;
            });
        },
    });

    window.addEventListener("carder:theme-changed", () => {
        Object.values(Chart.instances).forEach((chart) => chart.update("none"));
    });
})();
