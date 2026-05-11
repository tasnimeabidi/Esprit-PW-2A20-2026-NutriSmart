# -*- coding: utf-8 -*-
path = "plan-repas.html"
with open(path, "r", encoding="utf-8") as f:
    t = f.read()
t = t.replace(
    """          <a
            href="plan-repas-crud.html"
            class="day-card is-current"
            aria-label="Ouvrir la page de gestion plan repas, repas et sport"
          >""",
    """          <article
            class="day-card is-current js-open-plan-crud"
            tabindex="0"
            role="button"
            aria-label="Ouvrir la fenêtre CRUD plan repas, repas et sport"
          >""",
)
t = t.replace(
    """          <a
            href="plan-repas-crud.html"
            class="day-card"
            aria-label="Ouvrir la page de gestion plan repas, repas et sport"
          >""",
    """          <article
            class="day-card js-open-plan-crud"
            tabindex="0"
            role="button"
            aria-label="Ouvrir la fenêtre CRUD plan repas, repas et sport"
          >""",
)
t = t.replace("          </a>\n", "          </article>\n")
with open(path, "w", encoding="utf-8") as f:
    f.write(t)
print("day cards OK")
