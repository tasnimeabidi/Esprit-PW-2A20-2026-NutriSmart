document.addEventListener("DOMContentLoaded", function () {

    /* ============================================================
       RULES
    ============================================================ */
    const TITRE_MIN   = 5;
    const TITRE_MAX   = 500;
    const CONTENU_MIN = 10;
    const CONTENU_MAX = 1600;

    /* ============================================================
       SHARED HELPERS
    ============================================================ */

    /** Create or reuse an error <div> right after an element */
    function getErrorEl(input) {
        let err = input.nextElementSibling;
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
            `;
            input.parentNode.insertBefore(err, input.nextSibling);
        }
        return err;
    }

    function setError(input, msg) {
        getErrorEl(input).textContent = msg;
        input.style.borderColor = "#f44336";
    }

    function clearError(input) {
        getErrorEl(input).textContent = "";
        input.style.borderColor = "#a5d6a7";
    }

    /** Validate a titre input — returns true if valid */
    function validateTitre(input) {
        const val = input.value.trim();
        if (!val) {
            setError(input, "⚠️ Le titre est obligatoire.");
            return false;
        }
        if (val.length < TITRE_MIN) {
            setError(input, `⚠️ Titre trop court — minimum ${TITRE_MIN} caractères (actuellement ${val.length}).`);
            return false;
        }
        if (val.length > TITRE_MAX) {
            setError(input, `⚠️ Titre trop long — maximum ${TITRE_MAX} caractères (actuellement ${val.length}).`);
            return false;
        }
        clearError(input);
        return true;
    }

    /** Validate a contenu textarea — returns true if valid */
    function validateContenu(input, min, max) {
        const val = input.value.trim();
        if (!val) {
            setError(input, "⚠️ Le contenu est obligatoire.");
            return false;
        }
        if (val.length < min) {
            setError(input, `⚠️ Contenu trop court — minimum ${min} caractères (actuellement ${val.length}).`);
            return false;
        }
        if (val.length > max) {
            setError(input, `⚠️ Contenu trop long — maximum ${max} caractères (actuellement ${val.length}).`);
            return false;
        }
        clearError(input);
        return true;
    }

    /* ============================================================
       1. CREATE POST FORM  (#postForm)
    ============================================================ */
    const createForm    = document.getElementById("postForm");
    const createTitre   = createForm ? createForm.querySelector("input[name='titre']")    : null;
    const createContenu = createForm ? createForm.querySelector("textarea[name='contenu']") : null;

    if (createForm && createTitre && createContenu) {

        // live feedback
        createTitre.addEventListener("input",   () => validateTitre(createTitre));
        createContenu.addEventListener("input", () => validateContenu(createContenu, CONTENU_MIN, CONTENU_MAX));

        createForm.addEventListener("submit", function (e) {
            const t = validateTitre(createTitre);
            const c = validateContenu(createContenu, CONTENU_MIN, CONTENU_MAX);
            if (!t || !c) e.preventDefault();
        });
    }

    /* ============================================================
       2. EDIT POST FORMS  (forms containing input[name='id'])
       There can be multiple (one per post card), but only one
       is visible at a time (the one in ?edit= mode).
    ============================================================ */
    document.querySelectorAll("form[action*='action=update']").forEach(form => {

        const editTitre   = form.querySelector("input[name='titre']");
        const editContenu = form.querySelector("textarea[name='contenu']");

        if (!editTitre || !editContenu) return;

        // live feedback
        editTitre.addEventListener("input",   () => validateTitre(editTitre));
        editContenu.addEventListener("input", () => validateContenu(editContenu, CONTENU_MIN, CONTENU_MAX));

        form.addEventListener("submit", function (e) {
            const t = validateTitre(editTitre);
            const c = validateContenu(editContenu, CONTENU_MIN, CONTENU_MAX);
            if (!t || !c) e.preventDefault();
        });
    });

});