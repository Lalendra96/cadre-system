/** Shared form constraints; Laravel remains authoritative. No remote dependencies. */
(() => {
    "use strict";
    const leadingWhitespace = /^[\s\p{Z}\uFEFF]/u;
    const textTypes = new Set([
        "text",
        "search",
        "email",
        "url",
        "tel",
        "password",
    ]);
    const message = "Do not begin this field with spaces or other whitespace.";

    function check(field) {
        if (
            !(
                field instanceof HTMLInputElement ||
                field instanceof HTMLTextAreaElement
            )
        )
            return;
        if (
            field.disabled ||
            field.readOnly ||
            (field instanceof HTMLInputElement && !textTypes.has(field.type))
        )
            return;
        const invalid = leadingWhitespace.test(field.value);
        // Only clear the error owned by this module; preserve feature-specific constraints.
        if (invalid) {
            if (
                !field.validity.customError ||
                field.dataset.leadingWhitespaceError === "1"
            ) {
                field.setCustomValidity(message);
                field.dataset.leadingWhitespaceError = "1";
            }
        } else if (field.dataset.leadingWhitespaceError === "1") {
            field.setCustomValidity("");
            delete field.dataset.leadingWhitespaceError;
        }
        field.classList.toggle("has-leading-whitespace", invalid);
    }

    document.addEventListener("input", (event) => check(event.target), true);
    document.addEventListener("change", (event) => check(event.target), true);
    document.addEventListener("focusout", (event) => check(event.target), true);
    document.addEventListener(
        "submit",
        (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            Array.from(form.elements).forEach(check);
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                form.reportValidity();
            }
        },
        true,
    );
    document.addEventListener("reset", (event) => {
        setTimeout(() => Array.from(event.target.elements).forEach(check), 0);
    });
})();
