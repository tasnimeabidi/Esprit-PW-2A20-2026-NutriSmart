document.querySelectorAll(".ai-btn").forEach(btn => {

    btn.addEventListener("click", async function () {

        const postId = this.dataset.id;
        const content = this.dataset.content;

        const box = document.getElementById(`ai-result-${postId}`);

        const text = this.querySelector(".ai-text");
        const spinner = this.querySelector(".ai-spinner");

        if (!content || content.trim() === "") {
            box.innerHTML = "❌ Aucun contenu à résumer";
            return;
        }

        // =========================
        // START LOADING STATE
        // =========================
        text.innerHTML = "AI is thinking...";
        spinner.style.display = "inline-block";

        // disable button while loading (prevents spam clicks)
        this.disabled = true;
        this.style.opacity = "0.7";

        try {
            const res = await fetch("/ProjetNutrismart/ai_summary.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "content=" + encodeURIComponent(content)
            });

            const data = await res.json();

            if (data.summary) {

                box.innerHTML = `
                    <div style="
                        margin-top:10px;
                        background:#f7f7f7;
                        border-left:4px solid #4CAF50;
                        padding:12px;
                        border-radius:10px;
                        line-height:1.5;
                    ">
                        <strong>🧠 Résumé IA :</strong><br><br>
                        ${data.summary}
                    </div>
                `;

            } else {
                box.innerHTML = "❌ Erreur IA";
            }

        } catch (err) {
            console.log(err);
            box.innerHTML = "❌ API ERROR";
        }

        // =========================
        // RESET BUTTON STATE
        // =========================
        text.innerHTML = "✨ Résumer avec IA";
        spinner.style.display = "none";
        this.disabled = false;
        this.style.opacity = "1";
    });

});