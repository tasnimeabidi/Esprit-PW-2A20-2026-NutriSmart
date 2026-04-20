# -*- coding: utf-8 -*-
path = "plan-repas-crud.html"
with open(path, "r", encoding="utf-8") as f:
    lines = f.readlines()
body = "".join(lines[99:452])
body = body.replace(
    '        <div\n          id="plan-repas-crud-page"',
    '    <dialog\n      id="plan-repas-crud-dialog"',
    1,
)
body = body.replace(
    '          aria-labelledby="plan-repas-crud-title"\n        >',
    '      aria-modal="true"\n      aria-labelledby="plan-repas-crud-title"\n    >',
    1,
)
idx = body.rfind("    </div>")
if idx != -1:
    body = body[:idx] + "    </dialog>\n"
old_a = """        <a
          href="plan-repas.html"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/35 bg-transparent text-white no-underline transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50"
          aria-label="Retour au plan repas"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
        </a>"""
new_btn = """        <button
          type="button"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/35 bg-transparent text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50"
          data-plan-crud-close
          aria-label="Fermer la fenêtre"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M18 6L6 18M6 6l12 12" />
          </svg>
        </button>"""
if old_a in body:
    body = body.replace(old_a, new_btn)
else:
    raise SystemExit("back link block not found")
with open("_crud_dialog_fragment.html", "w", encoding="utf-8") as out:
    out.write(body)
print("OK", len(body))
