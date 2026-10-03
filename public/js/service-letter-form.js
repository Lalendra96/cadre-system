(() => {
    "use strict";

    const form = document.getElementById("letterForm");
    const employee = document.getElementById("employee_select");
    const template = document.getElementById("template_select");
    const language = document.getElementById("language_select");
    const body = document.getElementById("body_textarea");
    const subject = document.getElementById("subject_input");
    const status = document.getElementById("genStatus");
    const generateButton = document.getElementById("generateBtn");
    const aiButton = document.getElementById("aiBtn");
    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]',
    )?.content;

    if (
        !form ||
        !employee ||
        !template ||
        !language ||
        !body ||
        !subject ||
        !status
    ) {
        return;
    }

    const previewUrl = form.dataset.previewUrl;
    const aiUrl = form.dataset.aiUrl;

    function filterTemplates() {
        const selectedLanguage = language.value;

        Array.from(template.options).forEach((option, index) => {
            if (index === 0) {
                return;
            }

            option.hidden = option.dataset.language !== selectedLanguage;

            if (option.hidden && option.selected) {
                template.value = "";
            }
        });
    }

    language.addEventListener("change", filterTemplates);
    filterTemplates();

    template.addEventListener("change", () => {
        const option = template.options[template.selectedIndex];

        if (option?.value && !subject.value) {
            subject.value = option.text.replace(/\s*\([^)]*\)\s*$/, "");
        }
    });

    generateButton?.addEventListener("click", async () => {
        if (!employee.value || !template.value) {
            status.textContent =
                "Select an employee and matching template first.";
            return;
        }

        status.textContent = "Generating…";

        try {
            const response = await fetch(previewUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    employee_id: employee.value,
                    template_id: template.value,
                }),
            });

            if (!response.ok) {
                throw new Error("Template generation failed.");
            }

            const data = await response.json();
            body.value = data.rendered_body;
            status.textContent = "✓ Template generated. Review all facts.";
        } catch (error) {
            status.textContent = "Could not generate the template.";
        }
    });

    const preview = document.getElementById("aiDraftPreview");
    const previewText = document.getElementById("aiDraftText");
    const previewNotice = document.getElementById("aiDraftNotice");
    const applyButton = document.getElementById("applyAiDraft");
    let pending = null;
    let busy = false;
    const selection = () =>
        JSON.stringify([
            employee.value,
            template.value,
            language.value,
            document.getElementById("purpose_select")?.value,
            document.getElementById("ai_instructions")?.value,
        ]);

    function clearPreview() {
        pending = null;
        if (preview) preview.hidden = true;
        if (previewText) previewText.textContent = "";
    }
    [
        employee,
        template,
        language,
        document.getElementById("purpose_select"),
        document.getElementById("ai_instructions"),
    ].forEach((field) => {
        field?.addEventListener("input", clearPreview);
        field?.addEventListener("change", clearPreview);
    });
    document
        .getElementById("dismissAiDraft")
        ?.addEventListener("click", clearPreview);
    status.setAttribute("role", "status");
    status.setAttribute("aria-live", "polite");

    aiButton?.addEventListener("click", async () => {
        if (busy) return;
        if (
            !employee.value ||
            !document.getElementById("purpose_select")?.value
        ) {
            status.textContent = "Select an employee and letter purpose first.";
            return;
        }
        const instruction = document.getElementById("ai_instructions");
        if (instruction && !instruction.reportValidity()) return;
        clearPreview();
        const context = selection();
        busy = true;
        aiButton.disabled = true;
        if (generateButton) generateButton.disabled = true;
        status.textContent = "Preparing a draft for your review…";
        status.classList.add("ai-working");
        status.setAttribute("aria-busy", "true");
        try {
            const response = await fetch(aiUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    employee_id: employee.value,
                    template_id: template.value || null,
                    purpose: document.getElementById("purpose_select").value,
                    language: language.value,
                    instructions: instruction?.value || "",
                }),
            });
            if (!response.ok) throw new Error("AI drafting failed");
            const data = await response.json();
            if (context !== selection()) {
                status.textContent =
                    "Selection changed while drafting. Generate a new preview for the current selection.";
                return;
            }
            pending = { data, context };
            previewText.textContent = data.body;
            previewNotice.textContent = `${data.source_label}. ${data.notice} Audit reference: ${data.audit_reference}`;
            preview.hidden = false;
            preview.classList.add("ai-complete");
            status.textContent =
                "Preview ready. Review it below; your current letter body is unchanged.";
        } catch (error) {
            status.textContent =
                "Assistant unavailable. Continue with the template or manual workflow, or retry.";
        } finally {
            busy = false;
            aiButton.disabled = false;
            if (generateButton) generateButton.disabled = false;
            status.classList.remove("ai-working");
            status.setAttribute("aria-busy", "false");
        }
    });
    applyButton?.addEventListener("click", async () => {
        if (!pending || pending.context !== selection() || applyButton.disabled)
            return;
        const selected = pending;
        const previousBody = body.value;
        applyButton.disabled = true;
        try {
            const response = await fetch(form.dataset.aiApplyUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    employee_id: employee.value,
                    audit_reference: selected.data.audit_reference,
                }),
            });
            if (!response.ok) throw new Error("Could not record draft use");
            if (pending !== selected || selected.context !== selection())
                return;
            if (body.value !== previousBody) {
                status.textContent =
                    "The letter body changed while recording your selection. Review the preview and select Use this draft again if you still want to replace it.";
                return;
            }
            body.value = selected.data.body;
            body.dispatchEvent(new Event("input", { bubbles: true }));
            status.textContent =
                "Assisted draft placed in the editor and selection logged. Review and save the letter.";
            clearPreview();
        } catch (error) {
            status.textContent =
                "Could not log draft selection. Your letter body is unchanged; please retry.";
        } finally {
            applyButton.disabled = false;
        }
    });
})();
