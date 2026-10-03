(() => {
    "use strict";
    const button = document.querySelector("[data-ai-summary-url]");
    const output = document.getElementById("aiSummaryBox");
    if (!button || !output) return;
    button.addEventListener("click", async () => {
        if (button.disabled) return;
        button.disabled = true;
        output.setAttribute("aria-busy", "true");
        output.classList.remove("ai-complete");
        output.classList.add("ai-working");
        output.textContent = "Reviewing recorded service facts…";
        try {
            const response = await fetch(button.dataset.aiSummaryUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });
            if (!response.ok) throw new Error("Assistant unavailable");
            const result = await response.json();
            output.textContent = `${result.summary}\n\n${result.source_label}\n${result.notice}\nAudit reference: ${result.audit_reference}`;
            output.classList.add("ai-complete");
        } catch (error) {
            output.textContent =
                "Assistant unavailable. You can retry; the official record has not been changed.";
        } finally {
            output.classList.remove("ai-working");
            output.setAttribute("aria-busy", "false");
            button.disabled = false;
        }
    });
})();
