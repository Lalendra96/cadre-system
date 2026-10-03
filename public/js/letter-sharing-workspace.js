(function () {
    "use strict";

    const root = document.querySelector(".live-letter-workspace");
    if (!root) return;

    const csrf =
        document.querySelector('meta[name="csrf-token"]')?.content || "";
    const byId = (id) => document.getElementById(id);
    const editor = byId("live_content");
    const canEdit = root.dataset.canEdit === "1";
    let lockVersion = Number(root.dataset.lockVersion || 1);
    let dirty = false;
    let saving = false;
    let lastSelection = "";

    const tracked = [
        "title",
        "reference_no",
        "letterhead_id",
        "live_content",
        "classification",
        "personal_data",
        "retention",
        "review_due_date",
        "access_note",
    ];
    tracked.forEach((id) => {
        const el = byId(id);
        if (!el) return;
        const event =
            el.type === "checkbox" || el.tagName === "SELECT"
                ? "change"
                : "input";
        el.addEventListener(event, () => {
            if (!canEdit) return;
            dirty = true;
            setSaveState("Unsaved changes");
            updateWordCount();
        });
    });

    function setSaveState(message) {
        const el = byId("saveState");
        if (el) el.textContent = message;
    }

    function payload() {
        return {
            title: byId("title")?.value || "",
            reference_no: byId("reference_no")?.value || null,
            letterhead_id: byId("letterhead_id")?.value || null,
            live_content: editor?.value || "",
            document_classification:
                byId("classification")?.value || "internal",
            contains_personal_data: !!byId("personal_data")?.checked,
            retention_category:
                byId("retention")?.value || "official_correspondence",
            review_due_date: byId("review_due_date")?.value || null,
            access_note: byId("access_note")?.value || null,
            editor_lock_version: lockVersion,
        };
    }

    async function save(force) {
        if (!canEdit || saving || (!dirty && !force)) return;
        saving = true;
        setSaveState("Saving…");
        try {
            const response = await fetch(root.dataset.autosaveUrl, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify(payload()),
            });
            const data = await response.json().catch(() => ({}));
            if (response.status === 409) {
                dirty = false;
                setSaveState("Newer version detected — reload required");
                alert(
                    data.message ||
                        "Another officer saved a newer version. Reload before continuing.",
                );
                return;
            }
            if (!response.ok) throw new Error(data.message || "Save failed");
            lockVersion = Number(data.version || lockVersion);
            dirty = false;
            setSaveState(
                "Saved " +
                    new Date().toLocaleTimeString([], {
                        hour: "2-digit",
                        minute: "2-digit",
                    }),
            );
        } catch (error) {
            setSaveState(
                "Save failed — your current browser text has not been discarded",
            );
        } finally {
            saving = false;
        }
    }

    function updateWordCount() {
        const text = editor?.value || "";
        const count = text.trim() ? text.trim().split(/\s+/).length : 0;
        if (byId("wordCount")) byId("wordCount").textContent = count;
    }
    updateWordCount();

    function selectedText() {
        if (!editor || typeof editor.selectionStart !== "number") return "";
        return editor.value
            .substring(editor.selectionStart, editor.selectionEnd)
            .trim();
    }

    function focusComposer(type) {
        const quote = selectedText();
        lastSelection = quote;
        const quoteInput = byId("quotedText");
        const typeSelect = byId("commentType");
        const body = byId("commentBody");
        if (quoteInput) quoteInput.value = quote;
        if (typeSelect) typeSelect.value = type;
        document
            .querySelector(
                '[data-panel="' +
                    (type === "suggestion" ? "suggestions" : "comments") +
                    '"]',
            )
            ?.click();
        if (body) {
            body.placeholder =
                type === "suggestion"
                    ? "Describe the suggested change…"
                    : "Add a comment…";
            body.focus();
        }
    }

    function replaceSelectedLines(transform) {
        if (!canEdit || !editor) return;
        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        const value = editor.value;
        const beforeStart = value.lastIndexOf("\n", Math.max(0, start - 1)) + 1;
        const afterEndRaw = value.indexOf("\n", end);
        const afterEnd = afterEndRaw === -1 ? value.length : afterEndRaw;
        const block = value.substring(beforeStart, afterEnd);
        const replacement = transform(block);
        editor.setRangeText(replacement, beforeStart, afterEnd, "select");
        editor.dispatchEvent(new Event("input", { bubbles: true }));
        editor.focus();
    }

    document.querySelectorAll("[data-action]").forEach((button) => {
        button.addEventListener("click", async () => {
            const action = button.dataset.action;
            if (action === "save") return save(true);
            if (action === "comment") return focusComposer("comment");
            if (action === "suggest") return focusComposer("suggestion");
            if (action === "copy") {
                await navigator.clipboard?.writeText(editor?.value || "");
                setSaveState("Letter text copied");
                return;
            }
            if (action === "find") {
                const term = prompt("Find text in this letter:");
                if (!term || !editor) return;
                const index = editor.value
                    .toLowerCase()
                    .indexOf(term.toLowerCase());
                if (index < 0) return alert("Text not found.");
                editor.focus();
                editor.setSelectionRange(index, index + term.length);
                return;
            }
            if (!canEdit || !editor) return;
            if (action === "bullet")
                return replaceSelectedLines((block) =>
                    block
                        .split("\n")
                        .map((line) =>
                            line.trim()
                                ? "• " + line.replace(/^([•*-]|\d+[.)])\s*/, "")
                                : line,
                        )
                        .join("\n"),
                );
            if (action === "number")
                return replaceSelectedLines((block) =>
                    block
                        .split("\n")
                        .map((line, i) =>
                            line.trim()
                                ? i +
                                  1 +
                                  ". " +
                                  line.replace(/^([•*-]|\d+[.)])\s*/, "")
                                : line,
                        )
                        .join("\n"),
                );
            if (action === "indent")
                return replaceSelectedLines((block) =>
                    block
                        .split("\n")
                        .map((line) => "    " + line)
                        .join("\n"),
                );
            if (action === "outdent")
                return replaceSelectedLines((block) =>
                    block
                        .split("\n")
                        .map((line) => line.replace(/^ {1,4}/, ""))
                        .join("\n"),
                );
            if (action === "insert-date") {
                const text = new Intl.DateTimeFormat(undefined, {
                    day: "2-digit",
                    month: "long",
                    year: "numeric",
                }).format(new Date());
                editor.setRangeText(
                    text,
                    editor.selectionStart,
                    editor.selectionEnd,
                    "end",
                );
                editor.dispatchEvent(new Event("input", { bubbles: true }));
                return;
            }
            if (action === "undo") {
                editor.focus();
                document.execCommand("undo");
                return;
            }
            if (action === "redo") {
                editor.focus();
                document.execCommand("redo");
                return;
            }
        });
    });

    document.querySelectorAll(".llw-side-tab").forEach((tab) => {
        tab.addEventListener("click", () => {
            document
                .querySelectorAll(".llw-side-tab")
                .forEach((t) => t.classList.remove("active"));
            document
                .querySelectorAll(".llw-panel")
                .forEach((p) => p.classList.remove("active"));
            tab.classList.add("active");
            byId("panel-" + tab.dataset.panel)?.classList.add("active");
        });
    });
    document
        .querySelectorAll("[data-side-tab]")
        .forEach((button) =>
            button.addEventListener("click", () =>
                document
                    .querySelector(
                        '[data-panel="' + button.dataset.sideTab + '"]',
                    )
                    ?.click(),
            ),
        );

    byId("commentBtn")?.addEventListener("click", async () => {
        const body = byId("commentBody");
        const message = body?.value.trim();
        if (!message) return;
        const response = await fetch(root.dataset.commentUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrf,
            },
            body: JSON.stringify({
                body: message,
                comment_type: byId("commentType")?.value || "comment",
                quoted_text: byId("quotedText")?.value || lastSelection || null,
            }),
        });
        if (!response.ok)
            return alert("Unable to add the comment. Please try again.");
        window.location.reload();
    });

    async function heartbeat() {
        try {
            const response = await fetch(root.dataset.heartbeatUrl, {
                method: "POST",
                headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf },
            });
            if (!response.ok) return;
            const data = await response.json();
            const wrap = byId("presenceAvatars");
            if (!wrap) return;
            wrap.innerHTML = "";
            (data.people || []).slice(0, 5).forEach((person) => {
                const avatar = document.createElement("span");
                avatar.className = "llw-avatar";
                avatar.title = person.name + " is viewing";
                avatar.textContent = person.name
                    .split(/\s+/)
                    .map((v) => v[0] || "")
                    .join("")
                    .substring(0, 2)
                    .toUpperCase();
                wrap.appendChild(avatar);
            });
            if ((data.people || []).length > 5) {
                const more = document.createElement("span");
                more.className = "llw-avatar";
                more.textContent = "+" + ((data.people || []).length - 5);
                wrap.appendChild(more);
            }
        } catch (_) {}
    }

    setInterval(() => save(false), 4000);
    setInterval(heartbeat, 30000);
    heartbeat();

    document.addEventListener("keydown", (event) => {
        if (
            (event.ctrlKey || event.metaKey) &&
            event.key.toLowerCase() === "s"
        ) {
            event.preventDefault();
            save(true);
        }
        if (
            (event.ctrlKey || event.metaKey) &&
            event.key.toLowerCase() === "f" &&
            editor === document.activeElement
        ) {
            event.preventDefault();
            document.querySelector('[data-action="find"]')?.click();
        }
    });

    window.addEventListener("beforeunload", (event) => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = "";
    });
})();
