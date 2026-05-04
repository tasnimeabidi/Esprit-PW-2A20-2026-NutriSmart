document.addEventListener("DOMContentLoaded", () => {

    /* ============================================================
       SHARED HELPERS
    ============================================================ */

    const COMMENT_MIN = 3;
    const COMMENT_MAX = 500;

    /** Create or reuse an error <div> right after an element */
    function getErrorEl(el) {
        let err = el.nextElementSibling;
        if (!err || !err.classList.contains('inline-error')) {
            err = document.createElement("div");
            err.className = "inline-error";
            err.style.cssText = `
                color: #c62828;
                font-size: 0.78rem;
                font-weight: 600;
                margin-top: 4px;
                margin-bottom: 2px;
                min-height: 1rem;
                display: flex;
                align-items: center;
                gap: 4px;
            `;
            el.parentNode.insertBefore(err, el.nextSibling);
        }
        return err;
    }

    /** Validate a comment textarea — returns true if valid */
    function validateCommentField(textarea) {
        const err = getErrorEl(textarea);
        const val = textarea.value.trim();

        if (val === "") {
            err.textContent = "⚠️ Le commentaire ne peut pas être vide.";
            textarea.style.borderColor = "#f44336";
            return false;
        }
        if (val.length < COMMENT_MIN) {
            err.textContent = `⚠️ Minimum ${COMMENT_MIN} caractères (actuellement ${val.length}).`;
            textarea.style.borderColor = "#f44336";
            return false;
        }
        if (val.length > COMMENT_MAX) {
            err.textContent = `⚠️ Maximum ${COMMENT_MAX} caractères (actuellement ${val.length}).`;
            textarea.style.borderColor = "#f44336";
            return false;
        }

        err.textContent = "";
        textarea.style.borderColor = "#a5d6a7";
        return true;
    }

    /* ============================================================
       1. COMMENT POST FORMS — validate before submit
    ============================================================ */
    document.querySelectorAll("form[action*='add_comment']").forEach(form => {
        const textarea = form.querySelector("textarea[name='contenu']");
        if (!textarea) return;

        // live feedback while typing
        textarea.addEventListener("input", () => validateCommentField(textarea));

        form.addEventListener("submit", (e) => {
            if (!validateCommentField(textarea)) {
                e.preventDefault();
            }
        });
    });

    /* ============================================================
       2. COMMENT EDIT / SAVE (AJAX) — with validation on save
    ============================================================ */
    document.querySelectorAll(".comment").forEach(comment => {

        const editBtn = comment.querySelector(".edit-btn");
        const saveBtn = comment.querySelector(".save-btn");
        const text    = comment.querySelector(".comment-text");
        const box     = comment.querySelector(".edit-box");

        if (!editBtn || !saveBtn) return;

        // EDIT — show textarea
        editBtn.addEventListener("click", (e) => {
            e.preventDefault();
            text.style.display = "none";
            box.style.display  = "block";
            editBtn.style.display = "none";
            saveBtn.style.display = "inline-flex";
        });

        // live validation while editing
        box.addEventListener("input", () => validateCommentField(box));

        // SAVE (AJAX) — validate first
        saveBtn.addEventListener("click", async (e) => {
            e.preventDefault();

            if (!validateCommentField(box)) return;

            const id      = comment.dataset.id;
            const contenu = box.value.trim();

            const res = await fetch("/ProjetNutrismart/index.php?action=update_comment_ajax", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${id}&contenu=${encodeURIComponent(contenu)}`
            });

            const result = await res.text();

            if (result === "ok") {
                text.textContent = contenu;

                text.style.display = "block";
                box.style.display  = "none";

                editBtn.style.display = "inline-flex";
                saveBtn.style.display = "none";

                // clear any error
                const err = getErrorEl(box);
                err.textContent = "";
                box.style.borderColor = "";
            } else {
                const err = getErrorEl(box);
                err.textContent = "⚠️ Erreur lors de la sauvegarde. Réessayez.";
            }
        });
    });

    /* ============================================================
       3. CHARACTER COUNTER on all comment textareas
    ============================================================ */
    document.querySelectorAll("textarea[name='contenu']").forEach(textarea => {

        const counter = document.createElement("div");
        counter.style.cssText = `
            font-size: 0.75rem;
            margin-top: 3px;
            text-align: right;
            color: #748074;
        `;
        textarea.parentNode.appendChild(counter);

        function updateCounter() {
            const len = textarea.value.length;
            counter.textContent = `${len} / ${COMMENT_MAX}`;
            if (len > COMMENT_MAX) {
                counter.style.color = "#c62828";
            } else if (len > COMMENT_MAX * 0.9) {
                counter.style.color = "#f57c00"; // orange warning near limit
            } else {
                counter.style.color = "#748074";
            }
        }

        textarea.addEventListener("input", updateCounter);
        updateCounter();
    });

});