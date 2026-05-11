# -*- coding: utf-8 -*-
base = "plan-repas.html"
frag = "_crud_dialog_fragment.html"
with open(base, "r", encoding="utf-8") as f:
    t = f.read()
with open(frag, "r", encoding="utf-8") as f:
    dialog = f.read()
needle = "      </main>\n\n      <footer id=\"contact\">"
if needle not in t:
    raise SystemExit("needle not found")
t = t.replace(needle, "      </main>\n\n" + dialog + "\n\n      <footer id=\"contact\">", 1)
with open(base, "w", encoding="utf-8") as f:
    f.write(t)
print("dialog inserted")
