(() => {
    "use strict";

    const config = document.getElementById("secureLetterEditorConfig");

    if (!config) {
        return;
    }

    const canEdit = config.dataset.canEdit === "1";
    const csrfToken = config.dataset.csrf;
    const autosaveUrl = config.dataset.autosaveUrl;
    const heartbeatUrl = config.dataset.heartbeatUrl;
    const commentUrl = config.dataset.commentUrl;
    const resolveUrlTemplate = config.dataset.resolveUrlTemplate;

    let lockVersion = Number(config.dataset.lockVersion || 1);
    let dirty = false;
    let saveTimer = null;
    let saving = false;
    let conflictDetected = false;

    const element = (id) => document.getElementById(id);
    const editableIds = [
        "reference_no",
        "recipient_name",
        "recipient_address",
        "subject",
        "rendered_body",
        "document_classification",
        "contains_personal_data",
        "access_note",
    ];

    function setSaveState(text, mode = "ok") {
        const state = element("saveState");
        const dot = element("saveDot");

        if (!state || !dot) {
            return;
        }

        state.textContent = text;
        dot.className = "sec-dot";

        if (mode === "saving") {
            dot.classList.add("saving");
        } else if (mode === "error") {
            dot.classList.add("error");
        }
    }

    function scheduleSave() {
        if (!canEdit) {
            return;
        }

        dirty = true;
        setSaveState("Unsaved changes", "saving");
        clearTimeout(saveTimer);
        saveTimer = setTimeout(save, 4000);
    }

    function buildAutosavePayload() {
        return {
            subject: element("subject").value,
            rendered_body: element("rendered_body").value,
            reference_no: element("reference_no").value || null,
            recipient_name: element("recipient_name").value || null,
            recipient_address: element("recipient_address").value || null,
            document_classification: element("document_classification").value,
            contains_personal_data: element("contains_personal_data").checked,
            access_note: element("access_note").value || null,
            editor_lock_version: lockVersion,
        };
    }

    async function save() {
        if (!canEdit || !dirty || saving) {
            return;
        }

        saving = true;
        setSaveState("Saving…", "saving");

        try {
            const response = await fetch(autosaveUrl, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify(buildAutosavePayload()),
            });

            const data = await response.json().catch(() => ({}));

            if (response.status === 409) {
                conflictDetected = true;
                dirty = true;
                setSaveState("Conflict — reload required", "error");
                window.alert(
                    data.message ||
                        "A newer version exists. Reload before editing further.",
                );
                return;
            }

            if (!response.ok) {
                throw new Error(data.message || "Autosave failed");
            }

            lockVersion = Number(data.version);
            dirty = false;
            setSaveState("Saved just now");
        } catch (error) {
            setSaveState("Save failed — retrying", "error");
            dirty = true;
            saveTimer = setTimeout(save, 8000);
        } finally {
            saving = false;
        }
    }

    editableIds.forEach((id) => {
        const node = element(id);

        if (!node) {
            return;
        }

        node.addEventListener(
            node.type === "checkbox" ? "change" : "input",
            scheduleSave,
        );
    });

    element("document_classification")?.addEventListener("change", () => {
        const footer = element("footerClassification");

        if (footer) {
            footer.textContent = element(
                "document_classification",
            ).value.toUpperCase();
        }
    });

    element("reference_no")?.addEventListener("input", () => {
        const reference = element("reference_no");
        const check = element("referenceCheck");

        if (!reference || !check) {
            return;
        }

        const valid = reference.value.trim().length > 0;
        check.classList.toggle("warn", !valid);

        const icon = check.querySelector("i");
        if (icon) {
            icon.textContent = valid ? "✓" : "!";
        }
    });

    window.addEventListener("beforeunload", (event) => {
        if (!dirty) {
            return;
        }

        event.preventDefault();
        event.returnValue = "";
    });

    const submitForm = element("submitForReview");

    submitForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (conflictDetected) {
            window.alert(
                "This draft has a version conflict. Reload and review the newer version before submitting.",
            );
            return;
        }

        if (
            !window.confirm(
                "Submit this official letter for review? Editing will be locked until it is returned or approved.",
            )
        ) {
            return;
        }

        if (dirty) {
            await save();

            if (dirty || conflictDetected) {
                window.alert(
                    "The latest changes are not safely saved yet. Resolve the save status before submitting.",
                );
                return;
            }
        }

        HTMLFormElement.prototype.submit.call(submitForm);
    });

    function insertText(text) {
        if (!canEdit) {
            return;
        }

        const textarea = element("rendered_body");
        if (!textarea) {
            return;
        }

        textarea.setRangeText(
            text,
            textarea.selectionStart,
            textarea.selectionEnd,
            "end",
        );
        textarea.focus();
        scheduleSave();
    }

    document.querySelectorAll("[data-insert]").forEach((button) => {
        button.addEventListener("click", () =>
            insertText(button.dataset.insert),
        );
    });

    element("insertDate")?.addEventListener("click", () => {
        insertText(
            new Intl.DateTimeFormat("en-GB", {
                day: "2-digit",
                month: "long",
                year: "numeric",
            }).format(new Date()),
        );
    });

    document.querySelectorAll(".sec-tab").forEach((button) => {
        button.addEventListener("click", () => {
            document.querySelectorAll(".sec-tab").forEach((tab) => {
                tab.classList.remove("active");
            });

            document.querySelectorAll(".sec-tab-pane").forEach((pane) => {
                pane.classList.remove("active");
            });

            button.classList.add("active");
            document
                .querySelector(`[data-pane="${button.dataset.tab}"]`)
                ?.classList.add("active");
        });
    });

    async function heartbeat() {
        try {
            const response = await fetch(heartbeatUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const box = element("presenceList");

            if (!box) {
                return;
            }

            box.innerHTML = "";

            data.people.forEach((person) => {
                const row = document.createElement("div");
                const avatar = document.createElement("span");
                const details = document.createElement("div");
                const name = document.createElement("strong");
                const status = document.createElement("div");

                row.className = "sec-person";
                avatar.className = "sec-avatar";
                avatar.textContent = (person.name || "U")
                    .slice(0, 2)
                    .toUpperCase();
                name.style.fontSize = "12px";
                name.textContent = person.name;
                status.className = "sec-muted";
                status.textContent = "Active now";

                details.append(name, status);
                row.append(avatar, details);
                box.append(row);
            });
        } catch (error) {
            // Presence is advisory. Editor operations remain available if it fails.
        }
    }

    heartbeat();
    window.setInterval(heartbeat, 30000);

    element("addComment")?.addEventListener("click", async () => {
        const input = element("newComment");
        const body = input?.value.trim() || "";

        if (body.length < 2) {
            window.alert("Enter a review comment first.");
            return;
        }

        const response = await fetch(commentUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ body }),
        });

        if (!response.ok) {
            window.alert("Comment could not be saved.");
            return;
        }

        const data = await response.json();
        input.value = "";
        document.getElementById("noComments")?.remove();

        const item = document.createElement("div");
        const meta = document.createElement("div");
        const content = document.createElement("div");
        const resolveButton = document.createElement("button");

        item.className = "sec-comment";
        item.dataset.commentId = data.id;
        meta.className = "sec-comment-meta";
        meta.textContent = `${data.user} · just now`;
        content.className = "sec-comment-body";
        content.textContent = data.body;
        resolveButton.type = "button";
        resolveButton.className = "md-btn md-btn--text js-resolve-comment";
        resolveButton.dataset.id = data.id;
        resolveButton.style.cssText = "padding:4px 0;margin-top:4px;";
        resolveButton.textContent = "Resolve";

        item.append(meta, content, resolveButton);
        element("commentsList")?.prepend(item);
    });

    document.addEventListener("click", async (event) => {
        const button = event.target.closest(".js-resolve-comment");

        if (!button) {
            return;
        }

        const url = resolveUrlTemplate.replace(
            "__COMMENT__",
            button.dataset.id,
        );
        const response = await fetch(url, {
            method: "PATCH",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
        });

        if (!response.ok) {
            window.alert("Comment could not be resolved.");
            return;
        }

        const item = button.closest(".sec-comment");
        if (!item) {
            return;
        }

        item.style.opacity = ".58";
        button.remove();

        const meta = item.querySelector(".sec-comment-meta");
        if (meta) {
            meta.textContent += " · Resolved";
        }
    });
})();
