(() => {
    "use strict";
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)");
    if (!reduced.matches && "IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add("carder-enter");
                    observer.unobserve(entry.target);
                });
            },
            { threshold: 0.08 },
        );
        document
            .querySelectorAll(
                ".workforce-kpi,.cadre-kpi-card,.role-kpi,.md-stat-card,.kpi-card,.dashboard-widget,.role-quick-action,.cadre-dashboard-panel,.governance-stat-card,.governance-card",
            )
            .forEach((card, index) => {
                card.style.setProperty(
                    "--reveal-delay",
                    `${Math.min(index % 6, 5) * 55}ms`,
                );
                observer.observe(card);
            });
    }
    document.querySelectorAll("[data-temporal-replay]").forEach((panel) => {
        const button = panel.querySelector("[data-replay-timeline]");
        const status = panel.querySelector("[data-replay-status]");
        let timer;
        button?.addEventListener("click", () => {
            clearTimeout(timer);
            const count = panel.querySelectorAll(".temporal-event").length;
            if (!count) return;
            if (reduced.matches) {
                status.textContent = `${count} recorded events displayed. Motion is disabled by your device preference.`;
                return;
            }
            panel.classList.remove("is-replaying");
            void panel.offsetWidth;
            panel.classList.add("is-replaying");
            button.disabled = true;
            status.textContent =
                "Replaying displayed history. The snapshot is unchanged.";
            timer = setTimeout(
                () => {
                    panel.classList.remove("is-replaying");
                    button.disabled = false;
                    status.textContent = `${count} recorded events displayed. Replay complete.`;
                },
                520 + (count - 1) * 140,
            );
        });
    });
})();
