document.addEventListener("DOMContentLoaded", () => {

    /* =========================
       EDIT / SAVE AJAX (KEEPED)
    ========================= */
    document.querySelectorAll(".comment").forEach(comment => {

        const editBtn = comment.querySelector(".edit-btn");
        const saveBtn = comment.querySelector(".save-btn");
        const text = comment.querySelector(".comment-text");
        const box = comment.querySelector(".edit-box");

        if (!editBtn || !saveBtn) return;

        // EDIT
        editBtn.addEventListener("click", (e) => {
            e.preventDefault();

            text.style.display = "none";
            box.style.display = "block";

            editBtn.style.display = "none";
            saveBtn.style.display = "inline-block";
        });

        // SAVE (AJAX)
        saveBtn.addEventListener("click", async (e) => {
            e.preventDefault();

            const id = comment.dataset.id;
            const contenu = box.value.trim();

            if (contenu === "") return;

            const res = await fetch("/ProjetNutrismart/index.php?action=update_comment_ajax", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `id=${id}&contenu=${encodeURIComponent(contenu)}`
            });

            const result = await res.text();

            if (result === "ok") {

                text.textContent = contenu;

                text.style.display = "block";
                box.style.display = "none";

                editBtn.style.display = "inline-block";
                saveBtn.style.display = "none";
            }
        });
    });


    /* =========================
       COMMENT CHARACTER COUNTER
    ========================= */

    document.querySelectorAll("textarea[name='contenu']").forEach(textarea => {

        // create counter
        const counter = document.createElement("div");
        counter.style.fontSize = "12px";
        counter.style.marginTop = "5px";
        counter.style.color = "#748074";
        counter.style.textAlign = "right";

        textarea.parentNode.appendChild(counter);

        const max = 500; // same as your backend limit

        function updateCounter() {
            const len = textarea.value.length;

            counter.textContent = `${len} / ${max}`;

            if (len > max) {
                counter.style.color = "red";
            } else {
                counter.style.color = "#748074";
            }
        }

        textarea.addEventListener("input", updateCounter);

        updateCounter(); // init
    });

});