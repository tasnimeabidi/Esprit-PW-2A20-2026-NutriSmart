/**
 * Interface admin / front : onglets + formulaires + tableaux branchés sur NutriSmartCRUD.
 * Optionnel (front office) :
 *   window.NUTRISMART_CRUD_ADMIN_CONFIG = { root: "#fo-crud-plan", tabSelector: ".fo-crud-tab", ... }
 *   window.NUTRISMART_CRUD_ENTITIES = ["planRepas", "repas"]; // sous-ensemble
 */
(function () {
  "use strict";

  var CFG = (typeof window !== "undefined" && window.NUTRISMART_CRUD_ADMIN_CONFIG) || {};
  var ENTITIES =
    (typeof window !== "undefined" && window.NUTRISMART_CRUD_ENTITIES) || [
      "planRepas",
      "repas",
      "programmeSportif",
    ];
  var TAB_SEL = CFG.tabSelector || ".bo-crud-tab";
  var PANEL_SEL = CFG.panelSelector || ".bo-crud-panel";
  var MSG_CLASS = CFG.msgClassBase || "bo-crud-msg";
  var ACTIONS_CLASS = CFG.actionsClass || "bo-crud-actions";

  function entityEnabled(name) {
    return ENTITIES.indexOf(name) !== -1;
  }

  function rootEl() {
    if (CFG.root) {
      var r = document.querySelector(CFG.root);
      return r || document;
    }
    return document;
  }

  function $(id) {
    return document.getElementById(id);
  }

  function fixedActiviteFromPage() {
    var v = typeof document !== "undefined" && document.documentElement.getAttribute("data-ns-activite-fixe");
    return v ? String(v).trim() : "";
  }

  function lockProgrammeTypeSport(explicitValue) {
    var field = $("prog-type-sport");
    if (!field) return;
    var lockValue = String(explicitValue || "").trim() || fixedActiviteFromPage() || String(field.getAttribute("data-fixed-value") || "").trim();
    if (!lockValue) return;
    ensureSelectValue("prog-type-sport", lockValue);
    field.setAttribute("data-fixed-value", lockValue);
    if (field.tagName === "SELECT") {
      field.disabled = true;
      field.setAttribute("aria-disabled", "true");
    } else {
      field.readOnly = true;
      field.setAttribute("aria-readonly", "true");
    }
    document.querySelectorAll('[data-pr-target="prog-type-sport"]').forEach(function (btn) {
      btn.style.display = "none";
    });
  }

  function setupProgrammeTypeSportLocking() {
    var field = $("prog-type-sport");
    if (!field || field.getAttribute("data-lock-wired") === "1") return;
    field.setAttribute("data-lock-wired", "1");
    lockProgrammeTypeSport();
    if (field.tagName === "SELECT") {
      field.addEventListener("change", function () {
        var v = String(field.value || "").trim();
        if (v) lockProgrammeTypeSport(v);
      });
    }
  }

  function showMsg(text, isErr) {
    var el = $("crud-global-msg");
    if (!el) {
      if (!text) return;
      var toast = document.createElement("div");
      toast.textContent = text;
      toast.style.position = "fixed";
      toast.style.left = "50%";
      toast.style.top = "16px";
      toast.style.transform = "translateX(-50%)";
      toast.style.zIndex = "9999";
      toast.style.maxWidth = "min(92vw, 560px)";
      toast.style.padding = "12px 16px";
      toast.style.borderRadius = "10px";
      toast.style.boxShadow = "0 8px 20px rgba(0,0,0,0.18)";
      toast.style.fontFamily = "Inter, system-ui, sans-serif";
      toast.style.fontSize = "14px";
      toast.style.fontWeight = "600";
      toast.style.textAlign = "center";
      toast.style.lineHeight = "1.45";
      toast.style.whiteSpace = "pre-line";
      if (isErr) {
        toast.style.background = "#7f1d1d";
        toast.style.color = "#ffffff";
      } else {
        toast.style.background = "#1f5134";
        toast.style.color = "#ffffff";
      }
      document.body.appendChild(toast);
      window.setTimeout(function () {
        if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
      }, 4200);
      return;
    }
    el.textContent = text || "";
    el.className = MSG_CLASS;
  }

  function fillSelect(sel, items, valueKey, labelFn, emptyLabel, decorateOption) {
    if (!sel) return;
    var v = sel.value;
    sel.innerHTML = "";
    var o0 = document.createElement("option");
    o0.value = "";
    o0.textContent = emptyLabel || "—";
    sel.appendChild(o0);
    items.forEach(function (it) {
      var o = document.createElement("option");
      o.value = String(it[valueKey]);
      o.textContent = labelFn(it);
      if (decorateOption) decorateOption(it, o);
      sel.appendChild(o);
    });
    if (v && Array.prototype.some.call(sel.options, function (opt) { return opt.value === v; })) {
      sel.value = v;
    }
  }

  var editing = {
    planRepas: null,
    repas: null,
    programmeSportif: null,
  };

  function renderPlanRepas() {
    var tb = $("tbody-plan-repas");
    if (!tb) {
      refreshFkSelects();
      return;
    }
    var rows = NutriSmartCRUD.planRepas.list();
    tb.innerHTML = "";
    if (!rows.length) {
      var tr0 = document.createElement("tr");
      tr0.innerHTML =
        '<td colspan="7" class="ns-crud-empty">Aucun plan repas — remplissez le formulaire ci-dessus.</td>';
      tb.appendChild(tr0);
      refreshFkSelects();
      return;
    }
    rows.forEach(function (r, idx) {
      var tr = document.createElement("tr");
      var objRaw = r.objectif == null ? "" : String(r.objectif);
      var objCell = escapeHtml(objRaw);
      if (typeof window !== "undefined" && window.NutriSmartSaisieCrud && window.NutriSmartSaisieCrud.sanitizePlanObjectifLetters) {
        var san = window.NutriSmartSaisieCrud.sanitizePlanObjectifLetters(objRaw);
        if (objRaw.trim() !== "" && san === "") {
          objCell = "—";
        } else {
          objCell = escapeHtml(san);
        }
      }
      tr.innerHTML =
        "<td>" +
        (idx + 1) +
        "</td><td>" +
        escapeHtml(r.idUtilisateur) +
        "</td><td>" +
        escapeHtml(r.dateDebut) +
        "</td><td>" +
        escapeHtml(r.dateFin) +
        "</td><td>" +
        objCell +
        "</td><td>" +
        escapeHtml(r.statut) +
        '</td><td class="bo-crud-td-actions">' +
        actionButtonsHtml(r.id) +
        "</td>";
      tb.appendChild(tr);
    });
    refreshFkSelects();
  }

  /** Regroupe l’affichage quand plusieurs repas partagent plan + type + calories (ex. choix multiple de recettes). */
  function repasDisplayGroupKey(r) {
    var cal = String(caloriesAffichePourRepas(r) || "").trim();
    return [stripLeadingHashDisplay(String(r.idPlan || "")), String(r.type || "").trim(), cal].join("\u0001");
  }

  function groupRepasForDisplay(rows) {
    var sorted = rows.slice().sort(function (a, b) {
      var ka = repasDisplayGroupKey(a);
      var kb = repasDisplayGroupKey(b);
      if (ka !== kb) return ka < kb ? -1 : ka > kb ? 1 : 0;
      var na = parseInt(String(a.id), 10) || 0;
      var nb = parseInt(String(b.id), 10) || 0;
      return na - nb;
    });
    var groups = [];
    sorted.forEach(function (r) {
      var last = groups[groups.length - 1];
      if (!last || repasDisplayGroupKey(last[0]) !== repasDisplayGroupKey(r)) {
        groups.push([r]);
      } else {
        last.push(r);
      }
    });
    return groups;
  }

  function isBackofficeCrudPage() {
    if (typeof window === "undefined" || !window.location) return false;
    return String(window.location.pathname || "").toLowerCase().indexOf("/views/backoffice/") !== -1;
  }

  function renderRepas() {
    var tb = $("tbody-repas");
    if (!tb) return;
    var rows = NutriSmartCRUD.repas.list();
    tb.innerHTML = "";
    if (!rows.length) {
      var tr0 = document.createElement("tr");
      tr0.innerHTML =
        '<td colspan="6" class="ns-crud-empty">Aucun repas — choisissez un plan et enregistrez.</td>';
      tb.appendChild(tr0);
      refreshFkSelects();
      return;
    }
    if (isBackofficeCrudPage()) {
      rows.forEach(function (r) {
        var trDb = document.createElement("tr");
        trDb.innerHTML =
          "<td>" +
          escapeHtml(String(r.id || "")) +
          "</td><td>" +
          escapeHtml(planColonneTableAffiche(r)) +
          "</td><td>" +
          escapeHtml(recetteNomAffiche(r)) +
          "</td><td>" +
          escapeHtml(r.type) +
          "</td><td>" +
          escapeHtml(caloriesAffichePourRepas(r)) +
          '</td><td class="bo-crud-td-actions">' +
          actionButtonsHtml(r.id) +
          "</td>";
        tb.appendChild(trDb);
      });
      refreshFkSelects();
      return;
    }
    var groups = groupRepasForDisplay(rows);
    groups.forEach(function (group, rowIdx) {
      var primary = group[0];
      var tr = document.createElement("tr");
      var idCell = escapeHtml(String(rowIdx + 1));
      var nomsRecettes = group.map(function (r) {
        return recetteNomAffiche(r);
      });
      var nomCell = escapeHtml(nomsRecettes.join(" · "));
      var actionsHtml = repasGroupActionButtonsHtml(group);
      tr.innerHTML =
        "<td>" +
        idCell +
        "</td><td>" +
        escapeHtml(planColonneTableAffiche(primary)) +
        "</td><td>" +
        nomCell +
        "</td><td>" +
        escapeHtml(primary.type) +
        "</td><td>" +
        escapeHtml(caloriesAffichePourRepas(primary)) +
        '</td><td class="bo-crud-td-actions">' +
        actionsHtml +
        "</td>";
      tb.appendChild(tr);
    });
    refreshFkSelects();
  }

  function renderProgrammes() {
    var tb = $("tbody-programme");
    if (!tb) return;
    var rows = NutriSmartCRUD.programmeSportif.list();
    tb.innerHTML = "";
    if (!rows.length) {
      var tr0 = document.createElement("tr");
      tr0.innerHTML =
        '<td colspan="9" class="ns-crud-empty">Aucune séance — renseignez le formulaire puis enregistrez.</td>';
      tb.appendChild(tr0);
      refreshFkSelects();
      return;
    }
    rows.forEach(function (r, idx) {
      var tr = document.createElement("tr");
      tr.innerHTML =
        "<td>" +
        (idx + 1) +
        "</td><td>" +
        escapeHtml(planColonneTableAffiche(r)) +
        "</td><td>" +
        escapeHtml(r.typeSport) +
        "</td><td>" +
        escapeHtml(r.niveau) +
        "</td><td>" +
        escapeHtml(r.intensite) +
        "</td><td>" +
        escapeHtml(r.dateSeance) +
        "</td><td>" +
        escapeHtml(r.dureeMin) +
        "</td><td>" +
        escapeHtml(r.statut) +
        '</td><td class="bo-crud-td-actions">' +
        programmeActionButtonsHtml(r) +
        "</td>";
      tb.appendChild(tr);
    });
    refreshFkSelects();
  }

  function escapeHtml(s) {
    if (s == null) return "";
    var d = document.createElement("div");
    d.textContent = s;
    return d.innerHTML;
  }

  /** Boutons Modifier / Supprimer (liste) — classes CSS ns-crud-action */
  function actionButtonsHtml(id) {
    var sid = escapeHtml(String(id));
    return (
      '<div class="' +
      ACTIONS_CLASS +
      ' ns-crud-actions">' +
      '<button type="button" class="ns-crud-action ns-crud-action--edit" data-act="edit" data-id="' +
      sid +
      '" title="Modifier cet enregistrement">Modifier</button>' +
      '<button type="button" class="ns-crud-action ns-crud-action--delete" data-act="del" data-id="' +
      sid +
      '" title="Supprimer cet enregistrement">Supprimer</button>' +
      "</div>"
    );
  }

  function programmeActionButtonsHtml(row) {
    var sid = escapeHtml(String((row && row.id) || ""));
    return (
      '<div class="' +
      ACTIONS_CLASS +
      ' ns-crud-actions">' +
      '<button type="button" class="ns-crud-action ns-crud-action--edit" data-act="edit" data-id="' +
      sid +
      '" title="Modifier cet enregistrement">Modifier</button>' +
      '<button type="button" class="ns-crud-action ns-crud-action--delete" data-act="del" data-id="' +
      sid +
      '" title="Supprimer cet enregistrement">Supprimer</button>' +
      "</div>"
    );
  }

  /**
   * Toujours data-repas-group-ids = tous les id du groupe (même une seule ligne),
   * pour que Supprimer enlève toute la ligne affichée (ex. plusieurs recettes regroupées).
   */
  function repasGroupActionButtonsHtml(group) {
    if (!group || !group.length) return "";
    var ids = group.map(function (r) {
      return String(r.id);
    });
    var joined = ids.join(",");
    var primary = group[0];
    var sid = escapeHtml(String(primary.id));
    var escapedJoined = escapeHtml(joined);
    var delTitle = group.length > 1 ? "Supprimer tout le lot (toutes les recettes de cette ligne)" : "Supprimer cet enregistrement";
    return (
      '<div class="' +
      ACTIONS_CLASS +
      ' ns-crud-actions">' +
      '<button type="button" class="ns-crud-action ns-crud-action--edit" data-act="edit" data-id="' +
      sid +
      '" data-repas-group-ids="' +
      escapedJoined +
      '" title="Modifier cet enregistrement">Modifier</button>' +
      '<button type="button" class="ns-crud-action ns-crud-action--delete" data-act="del" data-id="' +
      sid +
      '" data-repas-group-ids="' +
      escapedJoined +
      '" title="' +
      escapeHtml(delTitle) +
      '">Supprimer</button>' +
      "</div>"
    );
  }

  function syncFormEditingState() {
    [
      { formId: "form-planRepas", key: "planRepas", defLabel: "Enregistrer" },
      { formId: "form-repas", key: "repas", defLabel: "Enregistrer" },
      { formId: "form-programmeSportif", key: "programmeSportif", defLabel: "Enregistrer" },
    ].forEach(function (spec) {
      var f = $(spec.formId);
      if (!f) return;
      var ed = editing[spec.key];
      f.classList.toggle("bo-crud-form--editing", !!ed);
      f.setAttribute("data-mode", ed ? "edit" : "create");
      var sub = f.querySelector('button[type="submit"]');
      if (sub) {
        sub.textContent = ed ? "Mettre à jour" : spec.defLabel;
      }
    });
  }

  function setFormVisible(name, visible) {
    var f = $("form-" + name);
    if (!f) return;
    f.hidden = !visible;
  }

  function formIdToEntity(formId) {
    if (formId === "form-planRepas") return "planRepas";
    if (formId === "form-repas") return "repas";
    if (formId === "form-programmeSportif") return "programmeSportif";
    return null;
  }

  function entityToFormId(entity) {
    if (entity === "planRepas") return "form-planRepas";
    if (entity === "repas") return "form-repas";
    if (entity === "programmeSportif") return "form-programmeSportif";
    return null;
  }

  function hideInlineCrudSuccess(formId) {
    var box = $("bo-crud-success-" + formId);
    if (box) box.hidden = true;
  }

  function showSuccessToast(message) {
    if (!message) return;
    var toast = document.createElement("div");
    toast.textContent = message;
    toast.style.position = "fixed";
    toast.style.left = "50%";
    toast.style.top = "16px";
    toast.style.transform = "translateX(-50%)";
    toast.style.zIndex = "9999";
    toast.style.background = "#1f5134";
    toast.style.color = "#ffffff";
    toast.style.padding = "12px 16px";
    toast.style.borderRadius = "10px";
    toast.style.boxShadow = "0 8px 20px rgba(0,0,0,0.18)";
    toast.style.fontFamily = "Inter, system-ui, sans-serif";
    toast.style.fontSize = "14px";
    toast.style.fontWeight = "600";
    toast.style.maxWidth = "min(92vw, 440px)";
    toast.style.textAlign = "center";
    toast.style.lineHeight = "1.45";
    toast.style.whiteSpace = "pre-line";
    document.body.appendChild(toast);
    window.setTimeout(function () {
      if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
    }, 4200);
  }

  function showDialogTopSuccess(form, message) {
    var dlg = form ? form.closest("dialog") : null;
    if (!dlg) return false;
    var host = dlg.querySelector(".fo-mem-crud-inner") || dlg;
    if (!host) return false;

    var old = host.querySelector(".ns-dialog-top-success");
    if (old && old.parentNode) old.parentNode.removeChild(old);

    var box = document.createElement("div");
    box.className = "ns-dialog-top-success";
    box.setAttribute("role", "status");
    box.setAttribute("aria-live", "polite");
    box.textContent = message || "Enregistrement réussi.";
    box.style.position = "sticky";
    box.style.top = "0";
    box.style.zIndex = "3";
    box.style.margin = "0 0 0.85rem";
    box.style.padding = "11px 14px";
    box.style.borderRadius = "10px";
    box.style.border = "1px solid #86efac";
    box.style.background = "#dcfce7";
    box.style.color = "#14532d";
    box.style.fontFamily = "Inter, system-ui, sans-serif";
    box.style.fontSize = "14px";
    box.style.fontWeight = "700";
    box.style.textAlign = "center";
    box.style.boxShadow = "0 4px 14px rgba(20,83,45,0.12)";

    host.insertBefore(box, host.firstChild);
    return true;
  }

  function clearDialogTopSuccess(form) {
    var dlg = form ? form.closest("dialog") : null;
    if (!dlg) return;
    var host = dlg.querySelector(".fo-mem-crud-inner") || dlg;
    if (!host) return;
    var old = host.querySelector(".ns-dialog-top-success");
    if (old && old.parentNode) old.parentNode.removeChild(old);
  }

  /**
   * Après enregistrement réussi : dans une <dialog>, fermeture + toast.
   * Sinon : formulaire masqué + message de succès dans le panneau + bouton pour un nouvel enregistrement.
   */
  function hideCrudCompletelyAndNotify(formId, message) {
    var form = $(formId);
    if (!form) return;
    var dlg = form.closest("dialog");
    if (dlg && typeof dlg.close === "function") {
      clearDialogTopSuccess(form);
      dlg.close();
      showSuccessToast(message || "Enregistrement réussi.");
      return;
    }

    form.hidden = true;

    var sid = "bo-crud-success-" + formId;
    var box = $(sid);
    var entityKey = formIdToEntity(formId);
    if (!box) {
      box = document.createElement("div");
      box.id = sid;
      box.className = "bo-crud-success-inline";
      box.setAttribute("role", "status");
      box.setAttribute("aria-live", "polite");

      var inner = document.createElement("div");
      inner.className = "bo-crud-success-inline__inner";

      var textP = document.createElement("p");
      textP.className = "bo-crud-success-inline__text";

      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "btn-sm btn-green";
      btn.textContent = "Nouvel enregistrement";
      btn.addEventListener("click", function () {
        box.hidden = true;
        if (entityKey) resetForm(entityKey);
      });

      inner.appendChild(textP);
      inner.appendChild(btn);
      box.appendChild(inner);
      form.parentNode.insertBefore(box, form.nextSibling);
    }

    var textEl = box.querySelector(".bo-crud-success-inline__text");
    if (textEl) textEl.textContent = message || "Enregistrement réussi.";
    box.hidden = false;
    try {
      box.scrollIntoView({ behavior: "smooth", block: "center" });
    } catch (_e) {}
  }

  function recetteNomAffiche(rowOrIdRec) {
    if (rowOrIdRec && typeof rowOrIdRec === "object") {
      var directNom = rowOrIdRec.recetteNom == null ? "" : String(rowOrIdRec.recetteNom).trim();
      if (directNom !== "") return directNom;
      rowOrIdRec = rowOrIdRec.idRecette;
    }
    var idStr = rowOrIdRec == null ? "" : String(rowOrIdRec).trim();
    if (idStr === "") return "—";
    var list =
      NutriSmartCRUD.recettes && NutriSmartCRUD.recettes.list
        ? NutriSmartCRUD.recettes.list()
        : [];
    var f = list.find(function (x) {
      return String(x.id) === idStr;
    });
    return f && f.nom ? f.nom : "#" + idStr;
  }

  function stripLeadingHashDisplay(s) {
    return String(s == null ? "" : s)
      .trim()
      .replace(/[#\uFF03]/g, "")
      .trim();
  }

  /** Même texte que les options du select plan (objectif, sinon id du plan) — cohérent avec le formulaire. */
  function planLibelleCommeFormulaireDepuisId(idPlan) {
    var idStr = idPlan == null ? "" : stripLeadingHashDisplay(String(idPlan));
    if (idStr === "") return "—";
    var plans =
      NutriSmartCRUD.planRepas && NutriSmartCRUD.planRepas.list
        ? NutriSmartCRUD.planRepas.list()
        : [];
    var p = plans.find(function (x) {
      return stripLeadingHashDisplay(String(x.id)) === idStr;
    });
    if (!p) return idStr;
    var obj = p.objectif == null ? "" : String(p.objectif).trim();
    if (obj !== "") return stripLeadingHashDisplay(obj);
    return String(p.id);
  }

  /**
   * Colonne PLAN (repas / programme) : préfère planObjectif joint (API) puis libellé comme au select.
   * @param {object|string|number} rowOrId — ligne { idPlan, planObjectif? } ou id de plan seul
   */
  function planColonneTableAffiche(rowOrId) {
    if (rowOrId && typeof rowOrId === "object" && "planObjectif" in rowOrId) {
      var po = rowOrId.planObjectif == null ? "" : String(rowOrId.planObjectif).trim();
      if (po !== "") return stripLeadingHashDisplay(po);
    }
    var idP =
      rowOrId && typeof rowOrId === "object" && "idPlan" in rowOrId
        ? rowOrId.idPlan
        : rowOrId;
    return planLibelleCommeFormulaireDepuisId(idP);
  }

  /** kcal depuis la liste recettes (API / cache), pas seulement l’attribut HTML. */
  function getCaloriesTotalesPourRecetteId(idStr) {
    if (idStr == null || String(idStr).trim() === "") return "";
    var list =
      NutriSmartCRUD.recettes && NutriSmartCRUD.recettes.list
        ? NutriSmartCRUD.recettes.list()
        : [];
    var f = list.find(function (x) {
      return String(x.id) === String(idStr);
    });
    if (!f) return "";
    var raw = f.caloriesTotales != null ? f.caloriesTotales : f.calories_totales;
    if (raw == null) return "";
    var t = String(raw).trim();
    return t;
  }

  /** Calories affichables pour l’option courante du select recette. */
  function caloriesPourRecetteSelectionnee(sel) {
    if (!sel || sel.tagName !== "SELECT") return "";
    var vid = sel.value ? String(sel.value).trim() : "";
    if (vid === "") return "";
    var fromList = getCaloriesTotalesPourRecetteId(vid);
    if (fromList !== "") return fromList;
    var opt = sel.options[sel.selectedIndex];
    return opt && opt.getAttribute("data-calories") ? opt.getAttribute("data-calories").trim() : "";
  }

  function patchRepasRecetteOptionsCaloriesMeta() {
    var sel = $("repas-id-recette");
    if (!sel || sel.tagName !== "SELECT") return;
    Array.prototype.forEach.call(sel.options, function (opt) {
      if (!opt.value) return;
      var ct = getCaloriesTotalesPourRecetteId(opt.value);
      if (ct !== "") opt.setAttribute("data-calories", ct);
    });
  }

  /** Colonne calories : valeur enregistrée, sinon calories de la recette choisie. */
  function caloriesAffichePourRepas(r) {
    var stored = r.calories == null ? "" : String(r.calories).trim();
    if (stored !== "") return stored;
    var idStr = r.idRecette == null ? "" : String(r.idRecette).trim();
    if (idStr === "") return "—";
    var list =
      NutriSmartCRUD.recettes && NutriSmartCRUD.recettes.list
        ? NutriSmartCRUD.recettes.list()
        : [];
    var f = list.find(function (x) {
      return String(x.id) === idStr;
    });
    if (!f) return "—";
    var raw = f.caloriesTotales != null ? f.caloriesTotales : f.calories_totales;
    if (raw != null && String(raw).trim() !== "") {
      return String(raw).trim();
    }
    return "—";
  }

  function refreshFkSelects() {
    var plans = NutriSmartCRUD.planRepas.list();
    if (entityEnabled("repas")) {
      fillSelect(
        $("repas-id-plan"),
        plans,
        "id",
        function (p) {
          var obj = p.objectif == null ? "" : String(p.objectif).trim();
          if (obj !== "") return stripLeadingHashDisplay(obj);
          return String(p.id);
        },
        "Choisir un plan"
      );
      var recs =
        NutriSmartCRUD.recettes && NutriSmartCRUD.recettes.list
          ? NutriSmartCRUD.recettes.list()
          : [];
      // Déduplication visuelle des recettes (accents/casse/espaces), en gardant une entrée persistable.
      var byName = {};
      recs.forEach(function (r) {
        if (!r) return;
        var nameKey = String(r.nom || "")
          .toLowerCase()
          .normalize("NFD")
          .replace(/[\u0300-\u036f]/g, "")
          .replace(/\s+/g, " ")
          .trim();
        if (!nameKey) return;
        var rid = String(r.id || "").trim();
        var isNumeric = /^[1-9]\d*$/.test(rid);
        if (!byName[nameKey]) {
          byName[nameKey] = r;
          return;
        }
        var oldRid = String(byName[nameKey].id || "").trim();
        var oldIsNumeric = /^[1-9]\d*$/.test(oldRid);
        if (isNumeric && !oldIsNumeric) {
          byName[nameKey] = r;
        }
      });
      recs = Object.keys(byName).map(function (k) {
        return byName[k];
      });
      fillSelect(
        $("repas-id-recette"),
        recs,
        "id",
        function (x) {
          var nom = x.nom || "Sans nom";
          return nom;
        },
        "— Aucune recette —",
        function (it, o) {
          var raw = it.caloriesTotales != null ? it.caloriesTotales : it.calories_totales;
          var ct = raw != null ? String(raw).trim() : "";
          if (ct !== "") o.setAttribute("data-calories", ct);
        }
      );
      var rsMulti = $("repas-id-recette");
      if (rsMulti && rsMulti.tagName === "SELECT") {
        rsMulti.multiple = true;
        rsMulti.size = Math.max(3, Math.min(8, recs.length || 3));
        var emptyOpt = rsMulti.querySelector('option[value=""]');
        if (emptyOpt) emptyOpt.remove();
        if (!editing.repas) {
          Array.prototype.forEach.call(rsMulti.options, function (opt) {
            opt.selected = false;
          });
        }
        ensureRecetteCheckboxGroup(rsMulti);
      }
      patchRepasRecetteOptionsCaloriesMeta();
      var rs = $("repas-id-recette");
      var rcal = $("repas-calories");
      if (rs && rcal && rs.value) onRepasRecetteSelectChange();
    }
    if (entityEnabled("programmeSportif")) {
      fillSelect(
        $("prog-id-plan"),
        plans,
        "id",
        function (p) {
          var obj = p.objectif == null ? "" : String(p.objectif).trim();
          if (obj !== "") return stripLeadingHashDisplay(obj);
          return String(p.id);
        },
        "Choisir un plan"
      );
    }
  }

  var LEGACY_SELECT_IDS = {
    programmeSportif: ["prog-type-sport", "prog-niveau", "prog-intensite", "prog-statut-seance"],
    planRepas: ["pr-statut", "pr-objectif"],
    repas: ["repas-id-recette", "repas-type"],
  };

  function stripLegacySelectsForForm(name) {
    var ids = LEGACY_SELECT_IDS[name];
    if (!ids) return;
    ids.forEach(function (id) {
      var sel = $(id);
      if (!sel || sel.tagName !== "SELECT") return;
      sel.querySelectorAll("option[data-legacy]").forEach(function (o) {
        o.remove();
      });
    });
  }

  /** Valeur en base absente de la liste → option temporaire pour l’édition (ou champ texte / hidden). */
  function ensureSelectValue(selectId, value) {
    var sel = $(selectId);
    if (!sel) return;
    var v = String(value == null ? "" : value);
    if (sel.tagName === "INPUT" || sel.tagName === "TEXTAREA") {
      sel.value = v;
      return;
    }
    if (sel.tagName !== "SELECT") return;
    sel.querySelectorAll("option[data-legacy]").forEach(function (o) {
      o.remove();
    });
    if (v === "") {
      sel.selectedIndex = 0;
      return;
    }
    var has = Array.prototype.some.call(sel.options, function (o) {
      return o.value === v;
    });
    if (!has) {
      var o = document.createElement("option");
      o.value = v;
      o.textContent = v;
      o.setAttribute("data-legacy", "1");
      sel.appendChild(o);
    }
    sel.value = v;
  }

  /** Champ calories piloté manuellement par l'utilisateur. */
  function applyRepasCaloriesReadonlyUi() {
    var inp = $("repas-calories");
    if (!inp) return;
    inp.readOnly = false;
    inp.classList.remove("ns-repas-calories-field--from-recette");
    if (
      typeof window !== "undefined" &&
      window.NutriSmartSaisieCrudUi &&
      window.NutriSmartSaisieCrudUi.refreshRepasCaloriesFieldSuccessUi
    ) {
      window.NutriSmartSaisieCrudUi.refreshRepasCaloriesFieldSuccessUi(inp);
    }
  }

  function onRepasRecetteSelectChange() {
    var inp = $("repas-calories");
    if (!inp) return;
    inp.readOnly = false;
    inp.classList.remove("ns-repas-calories-field--from-recette");
    if (
      typeof window !== "undefined" &&
      window.NutriSmartSaisieCrudUi &&
      window.NutriSmartSaisieCrudUi.refreshRepasCaloriesFieldSuccessUi
    ) {
      window.NutriSmartSaisieCrudUi.refreshRepasCaloriesFieldSuccessUi(inp);
    }
  }

  function ensureRecetteCheckboxGroup(sel) {
    if (!sel || sel.tagName !== "SELECT") return;
    var holderId = "repas-id-recette-checklist";
    var old = document.getElementById(holderId);
    if (old) old.remove();

    sel.style.display = "none";
    var holder = document.createElement("div");
    holder.id = holderId;
    holder.className = "ns-repas-recette-checklist";
    holder.style.display = "grid";
    holder.style.gap = "0.4rem";
    holder.style.marginTop = "0.25rem";

    Array.prototype.forEach.call(sel.options, function (opt) {
      if (!opt.value) return;
      var row = document.createElement("div");
      row.className = "ns-repas-recette-item";

      var cb = document.createElement("input");
      cb.type = "checkbox";
      cb.value = String(opt.value);
      cb.checked = !!opt.selected;
      cb.addEventListener("change", function () {
        opt.selected = cb.checked;
      });

      var txt = document.createElement("span");
      txt.className = "ns-repas-recette-text";
      txt.textContent = opt.textContent || "";

      row.appendChild(cb);
      row.appendChild(txt);
      holder.appendChild(row);
    });

    var help = document.createElement("small");
    help.className = "ns-repas-recette-help";
    help.textContent = "Vous pouvez choisir plusieurs recettes.";
    holder.appendChild(help);

    sel.parentNode.insertBefore(holder, sel.nextSibling);
  }

  function syncRecetteCheckboxGroupFromSelect(sel) {
    if (!sel || sel.tagName !== "SELECT") return;
    var checklist = document.getElementById("repas-id-recette-checklist");
    if (!checklist) return;
    var selectedMap = {};
    Array.prototype.forEach.call(sel.selectedOptions || [], function (opt) {
      selectedMap[String(opt.value || "")] = true;
    });
    checklist.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
      cb.checked = !!selectedMap[String(cb.value || "")];
    });
  }

  function selectedRecetteIds() {
    var checklist = document.getElementById("repas-id-recette-checklist");
    if (checklist) {
      return Array.prototype.map
        .call(checklist.querySelectorAll('input[type="checkbox"]:checked'), function (cb) {
          return String(cb.value || "").trim();
        })
        .filter(function (v) {
          return v !== "";
        });
    }
    var sel = $("repas-id-recette");
    if (!sel || sel.tagName !== "SELECT") return [];
    return Array.prototype.map
      .call(sel.selectedOptions || [], function (o) {
        return String(o.value || "").trim();
      })
      .filter(function (v) {
        return v !== "";
      });
  }

  function normalizeRecetteName(v) {
    return String(v || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/\s+/g, " ")
      .trim();
  }

  // Convertit un id éventuellement demo-* vers un id recette numérique persistable.
  function resolveRecetteIdForPersist(rawId) {
    var sid = String(rawId || "").trim();
    if (!sid) return "";
    if (/^[1-9]\d*$/.test(sid)) return sid;
    var list = NutriSmartCRUD.recettes && NutriSmartCRUD.recettes.list ? NutriSmartCRUD.recettes.list() : [];
    var src = list.find(function (r) {
      return String(r && r.id != null ? r.id : "").trim() === sid;
    });
    if (!src) return "";
    var srcName = normalizeRecetteName(src.nom);
    if (!srcName) return "";
    var match = list.find(function (r) {
      var rid = String(r && r.id != null ? r.id : "").trim();
      if (!/^[1-9]\d*$/.test(rid)) return false;
      return normalizeRecetteName(r.nom) === srcName;
    });
    return match ? String(match.id) : "";
  }

  function toPersistedRecetteIds(selectedIds) {
    var out = [];
    (selectedIds || []).forEach(function (id) {
      var pid = resolveRecetteIdForPersist(id);
      if (pid) out.push(pid);
    });
    return out;
  }

  function setSelectedRecetteIds(ids) {
    var sel = $("repas-id-recette");
    if (!sel || sel.tagName !== "SELECT") return;
    var wanted = {};
    (ids || []).forEach(function (id) {
      var sid = String(id || "").trim();
      if (sid) wanted[sid] = true;
    });
    Array.prototype.forEach.call(sel.options, function (opt) {
      if (!opt.value) return;
      opt.selected = !!wanted[String(opt.value || "").trim()];
    });
    syncRecetteCheckboxGroupFromSelect(sel);
  }

  function resetForm(name) {
    var fid = entityToFormId(name);
    if (fid) hideInlineCrudSuccess(fid);
    setFormVisible(name, true);
    editing[name] = null;
    stripLegacySelectsForForm(name);
    var form = $("form-" + name);
    if (form) {
      clearDialogTopSuccess(form);
      form.reset();
      if (typeof window !== "undefined" && window.NutriSmartSaisieCrudUi) {
        window.NutriSmartSaisieCrudUi.clearFieldErrors(form);
      }
    }
    if (name === "repas") {
      var frEdit = $("form-repas");
      if (frEdit) {
        frEdit.removeAttribute("data-editing-id");
        frEdit.removeAttribute("data-editing-group-ids");
      }
      var ci = $("repas-calories");
      if (ci) {
        ci.readOnly = false;
        ci.classList.remove("ns-repas-calories-field--from-recette");
      }
    }
    if (name === "programmeSportif") {
      lockProgrammeTypeSport();
    }
      if (name === "planRepas") {
        var pid = $("pr-id");
        if (pid) pid.value = "";
      }
    setEditingHint(name, null);
    syncFormEditingState();
  }

  function setEditingHint(name, id) {
    var hint = $("hint-" + name);
    if (hint) {
      if (id) {
        hint.textContent = "Mode édition · enregistrement n° " + id + " — validez avec « Mettre à jour » ou annulez avec « Nouveau ».";
        hint.classList.add("bo-crud-hint--editing");
      } else {
        hint.textContent = "";
        hint.classList.remove("bo-crud-hint--editing");
      }
    }
  }

  function switchTab(name) {
    rootEl().querySelectorAll(TAB_SEL).forEach(function (btn) {
      var on = btn.getAttribute("data-tab") === name;
      btn.classList.toggle("is-active", on);
      btn.setAttribute("aria-selected", on ? "true" : "false");
    });
    rootEl().querySelectorAll(PANEL_SEL).forEach(function (panel) {
      panel.hidden = panel.getAttribute("data-panel") !== name;
    });
    if (typeof window !== "undefined" && window.NutriSmartSaisieCrudUi) {
      window.NutriSmartSaisieCrudUi.clearFieldErrors(rootEl());
    }
  }

  function bindTableActions(tbodyId, entity, onRefresh) {
    var tb = $(tbodyId);
    if (!tb) return;
    tb.addEventListener("click", function (e) {
      var btn = e.target.closest("button[data-act]");
      if (!btn) return;
      var id = btn.getAttribute("data-id");
      var act = btn.getAttribute("data-act");
      var lotIdsAttr = entity === "repas" ? btn.getAttribute("data-repas-group-ids") : null;
      if (act === "metierSport" && entity === "programmeSportif") {
        if (!id) return;
        var target = "metier-avance.html?idProgramme=" + encodeURIComponent(String(id));
        window.location.href = target;
        return;
      }
      if (act === "del") {
        if (entity === "repas" && lotIdsAttr && lotIdsAttr.trim()) {
          var lotList = lotIdsAttr.split(",").map(function (s) {
            return s.trim();
          }).filter(Boolean);
          if (lotList.length) {
            var confirmMsg =
              lotList.length > 1
                ? "Supprimer définitivement les " +
                  lotList.length +
                  " repas de cette ligne (n° " +
                  lotList.join(", ") +
                  ") ? Cette action ne peut pas être annulée."
                : "Supprimer définitivement l’enregistrement n° " +
                  lotList[0] +
                  " ? Cette action ne peut pas être annulée.";
            if (!confirm(confirmMsg)) return;
            var chainRepas = Promise.resolve();
            lotList.forEach(function (rid) {
              chainRepas = chainRepas.then(function () {
                return Promise.resolve(NutriSmartCRUD.repas.delete(rid)).then(function (delRes) {
                  if (delRes && delRes.error) {
                    return Promise.reject(new Error(delRes.error));
                  }
                });
              });
            });
            chainRepas
              .then(function () {
                showMsg(lotList.length > 1 ? "Ligne supprimée (tous les repas du groupe)." : "Supprimé.");
                if (NutriSmartCRUD.init && NutriSmartCRUD.isApi && NutriSmartCRUD.isApi()) {
                  return NutriSmartCRUD.init()
                    .then(function () {
                      onRefresh();
                      renderAll();
                    })
                    .catch(function () {
                      onRefresh();
                      renderAll();
                    });
                }
                onRefresh();
                renderAll();
              })
              .catch(function (err) {
                showMsg(err && err.message ? err.message : "Erreur lors de la suppression.", true);
                onRefresh();
                renderAll();
              });
            return;
          }
        }
        var confirmDeleteMsg =
          entity === "planRepas"
            ? "Supprimer définitivement le plan n° " +
              id +
              " ? Cette action supprimera aussi les repas et programmes sportifs liés (cascade) et ne peut pas être annulée."
            : "Supprimer définitivement l’enregistrement n° " + id + " ? Cette action ne peut pas être annulée.";
        if (!confirm(confirmDeleteMsg))
          return;
        Promise.resolve(NutriSmartCRUD[entity].delete(id)).then(function (delRes) {
          if (delRes && delRes.error) {
            showMsg(delRes.error, true);
            return;
          }
          showMsg(entity === "planRepas" ? "Plan supprimé (cascade repas + programmes sportifs)." : "Supprimé.");
          var reloadFromApi =
            NutriSmartCRUD.init &&
            NutriSmartCRUD.isApi &&
            NutriSmartCRUD.isApi() &&
            (entity === "repas" || entity === "planRepas");
          if (reloadFromApi) {
            return NutriSmartCRUD.init()
              .then(function () {
                onRefresh();
                renderAll();
              })
              .catch(function () {
                onRefresh();
                renderAll();
              });
          }
          onRefresh();
          renderAll();
        });
        return;
      }
      if (act === "edit") {
        var row = NutriSmartCRUD[entity].get(id);
        if (!row) return;
        var eFid = entityToFormId(entity);
        if (eFid) hideInlineCrudSuccess(eFid);
        setFormVisible(entity, true);
        editing[entity] = id;
        setEditingHint(entity, id);
        if (entity === "planRepas") {
          if ($("pr-id")) $("pr-id").value = row.id || "";
          $("pr-id-utilisateur").value = row.idUtilisateur || "";
          $("pr-date-debut").value = row.dateDebut || "";
          $("pr-date-fin").value = row.dateFin || "";
          ensureSelectValue("pr-objectif", row.objectif || "");
          ensureSelectValue("pr-statut", row.statut || "");
        } else if (entity === "repas") {
          var frSet = $("form-repas");
          if (frSet) frSet.setAttribute("data-editing-id", String(row.id || id || ""));
          if (frSet) {
            if (lotIdsAttr && lotIdsAttr.trim()) frSet.setAttribute("data-editing-group-ids", lotIdsAttr.trim());
            else frSet.removeAttribute("data-editing-group-ids");
          }
          $("repas-id-plan").value = stripLeadingHashDisplay(row.idPlan || "");
          ensureSelectValue("repas-id-recette", row.idRecette || "");
          if (lotIdsAttr && lotIdsAttr.trim()) {
            var idsGroup = lotIdsAttr.split(",").map(function (s) {
              return String(s || "").trim();
            }).filter(Boolean);
            var recIds = [];
            idsGroup.forEach(function (rid) {
              var rrow = NutriSmartCRUD.repas.get(rid);
              if (!rrow) return;
              var recId = String(rrow.idRecette || "").trim();
              if (recId) recIds.push(recId);
            });
            if (recIds.length) setSelectedRecetteIds(recIds);
            else syncRecetteCheckboxGroupFromSelect($("repas-id-recette"));
          } else {
            syncRecetteCheckboxGroupFromSelect($("repas-id-recette"));
          }
          ensureSelectValue("repas-type", row.type || "");
          var rowCal = String(row.calories || "").trim();
          var calInp = $("repas-calories");
          if (calInp) calInp.value = rowCal;
          var selR = $("repas-id-recette");
          var dcR = getCaloriesTotalesPourRecetteId(String(row.idRecette || "").trim());
          if (dcR === "" && selR) {
            var optR = selR.options[selR.selectedIndex];
            dcR = optR && optR.getAttribute("data-calories") ? optR.getAttribute("data-calories").trim() : "";
          }
          if (dcR !== "" && rowCal === "" && calInp) calInp.value = dcR;
          applyRepasCaloriesReadonlyUi();
        } else if (entity === "programmeSportif") {
          $("prog-id-plan").value = stripLeadingHashDisplay(row.idPlan || "");
          var fixeAct = fixedActiviteFromPage();
          ensureSelectValue("prog-type-sport", fixeAct || row.typeSport || "");
          lockProgrammeTypeSport(fixeAct || row.typeSport || "");
          ensureSelectValue("prog-niveau", row.niveau || "");
          ensureSelectValue("prog-intensite", row.intensite || "");
          if ($("prog-date-seance")) $("prog-date-seance").value = row.dateSeance || "";
          if ($("prog-duree-min")) $("prog-duree-min").value = row.dureeMin || "";
          ensureSelectValue("prog-statut-seance", row.statut || "");
        }
        switchTab(entity);
        syncFormEditingState();
      }
    });
  }

  function renderAll() {
    if (entityEnabled("planRepas")) renderPlanRepas();
    if (entityEnabled("repas")) renderRepas();
    if (entityEnabled("programmeSportif")) renderProgrammes();
  }

  function focusProgrammeTableIfPresent() {
    var tb = $("tbody-programme");
    if (!tb || tb.tagName !== "TBODY") return;
    var table = tb.closest("table");
    if (!table) return;
    try {
      table.scrollIntoView({ behavior: "smooth", block: "center" });
    } catch (_e) {}
    var firstDataRow = tb.querySelector("tr td");
    if (firstDataRow) {
      firstDataRow.style.transition = "background-color 0.35s";
      firstDataRow.style.backgroundColor = "#dcfce7";
      window.setTimeout(function () {
        firstDataRow.style.backgroundColor = "";
      }, 1100);
    }
  }

  function openMetierSportFromSelection() {
    var progId = "";
    if (editing.programmeSportif) progId = String(editing.programmeSportif);
    if (!progId) {
      var list = NutriSmartCRUD.programmeSportif.list ? NutriSmartCRUD.programmeSportif.list() : [];
      var selectedPlan = $("prog-id-plan") ? String($("prog-id-plan").value || "").trim() : "";
      if (selectedPlan) {
        var found = list.find(function (r) {
          return String((r && r.idPlan) || "").trim() === selectedPlan;
        });
        if (found && found.id != null) progId = String(found.id);
      }
    }
    if (progId) {
      window.location.href = "metier-avance.html?idProgramme=" + encodeURIComponent(progId);
      return;
    }
    var planId = $("prog-id-plan") ? String($("prog-id-plan").value || "").trim() : "";
    if (planId) {
      window.location.href = "metier-avance.html?idPlan=" + encodeURIComponent(planId);
      return;
    }
    showMsg("Choisissez une séance (ou un plan) avant d’ouvrir le métier avancé sport.", true);
  }

  document.addEventListener("DOMContentLoaded", function () {
    function boot() {
      if (NutriSmartCRUD.isApi && NutriSmartCRUD.isApi()) {
        showMsg("", false);
      }
      if (!NutriSmartCRUD.isApi || !NutriSmartCRUD.isApi()) {
        NutriSmartCRUD.seedDemo();
      }
      renderAll();
      syncFormEditingState();
      document.dispatchEvent(new CustomEvent("nutrismartCrudReady", { bubbles: true }));
    }

    if (NutriSmartCRUD.init) {
      NutriSmartCRUD.init().then(function (ok) {
        boot();
        setupProgrammeTypeSportLocking();
        if (!ok && typeof window.NUTRISMART_API_BASE === "string" && window.NUTRISMART_API_BASE) {
          var h = window.NUTRISMART_CRUD_LAST_HEALTH;
          var detail = h && h.error ? " " + h.error : "";
          showMsg(
            "Données non enregistrées en MySQL — mode navigateur (localStorage). Les lignes et IDs ne correspondent pas à phpMyAdmin." +
              " Vérifiez MySQL, import database/nutrismart.sql et config/database.php." +
              detail,
            true
          );
        } else if (!ok && (!window.NUTRISMART_API_BASE || !String(window.NUTRISMART_API_BASE).trim())) {
          showMsg(
            "Ouvrez cette page via http://localhost/... (pas file://) ou définissez window.NUTRISMART_API_BASE vers le dossier api/. Sinon les changements restent dans le navigateur seulement.",
            true
          );
        }
      });
    } else {
      boot();
      setupProgrammeTypeSportLocking();
    }

    rootEl().querySelectorAll(TAB_SEL).forEach(function (btn) {
      btn.addEventListener("click", function () {
        switchTab(btn.getAttribute("data-tab"));
      });
    });

    if (entityEnabled("planRepas")) bindTableActions("tbody-plan-repas", "planRepas", renderAll);
    if (entityEnabled("repas")) bindTableActions("tbody-repas", "repas", renderAll);
    if (entityEnabled("programmeSportif")) bindTableActions("tbody-programme", "programmeSportif", renderAll);

    if (typeof BroadcastChannel !== "undefined" && !window.__NUTRISMART_BC_SYNC_LISTENER__) {
      try {
        window.__NUTRISMART_BC_SYNC_LISTENER__ = true;
        var bcSync = new BroadcastChannel("nutrismart-crud-sync");
        bcSync.onmessage = function (ev) {
          if (!ev || !ev.data || ev.data.type !== "programmeSportif") return;
          if (!NutriSmartCRUD.init) return;
          NutriSmartCRUD.init().then(function () {
            renderAll();
            if ($("crud-global-msg")) {
              showMsg("Programme sportif : liste actualisée (enregistrement depuis un autre onglet).", false);
            }
          });
        };
      } catch (_bcListenErr) {}
    }

    /* attachLiveClear : déjà branché sur ces formulaires dans nutrismart-saisie-crud.js (DOMContentLoaded).
       Ne pas rappeler ici : double listener sur repas-calories vidait le champ puis effaçait l’erreur « lettres ». */

    var fp = $("form-planRepas");
    if (fp) fp.addEventListener("submit", function (e) {
      e.preventDefault();
      var payload = {
        idUtilisateur: $("pr-id-utilisateur").value.trim(),
        dateDebut: $("pr-date-debut").value,
        dateFin: $("pr-date-fin").value,
        objectif: $("pr-objectif").value.trim(),
        statut: $("pr-statut").value.trim(),
      };
      if (typeof window !== "undefined" && window.NutriSmartSaisieCrud && window.NutriSmartSaisieCrudUi) {
        var fePlan = window.NutriSmartSaisieCrud.planRepasFieldErrors(payload);
        if (fePlan.length) {
          showMsg("Veuillez corriger les champs surlignés en rouge.", true);
          window.NutriSmartSaisieCrudUi.showFieldErrors(fp, fePlan);
          return;
        }
        window.NutriSmartSaisieCrudUi.clearFieldErrors(fp);
      }
      var op = editing.planRepas
        ? NutriSmartCRUD.planRepas.update(editing.planRepas, payload)
        : NutriSmartCRUD.planRepas.create(payload);
      Promise.resolve(op).then(function (res) {
        if (res && res.error) {
          var apiE =
            window.NutriSmartSaisieCrud && window.NutriSmartSaisieCrud.apiErrorsForPlanRepas
              ? window.NutriSmartSaisieCrud.apiErrorsForPlanRepas(res.error)
              : [];
          if (apiE.length && window.NutriSmartSaisieCrudUi) {
            showMsg("", false);
            window.NutriSmartSaisieCrudUi.showFieldErrors(fp, apiE);
          } else {
            showMsg(res.error, true);
          }
          return;
        }
        if (window.NutriSmartSaisieCrudUi) window.NutriSmartSaisieCrudUi.clearFieldErrors(fp);
        showMsg(editing.planRepas ? "Plan repas mis à jour." : "Plan repas créé.");
        resetForm("planRepas");
        renderAll();
        hideCrudCompletelyAndNotify("form-planRepas", "Plan repas enregistre avec succes.");
      });
    });

    var fr = $("form-repas");
    if (fr) fr.addEventListener("submit", function (e) {
      e.preventDefault();
      var editingRepasId = String(fr.getAttribute("data-editing-id") || editing.repas || "").trim();
      var isRepasEditing = /^[1-9]\d*$/.test(editingRepasId);
      var editingGroupIds = String(fr.getAttribute("data-editing-group-ids") || "")
        .split(",")
        .map(function (s) {
          return String(s || "").trim();
        })
        .filter(function (s) {
          return /^[1-9]\d*$/.test(s);
        });
      var selectedIds = selectedRecetteIds();
      var persistedIds = toPersistedRecetteIds(selectedIds);
      var idRecetteVal = persistedIds.length ? persistedIds[0] : "";
      var caloriesVal = $("repas-calories").value.trim();
      var payload = {
        idPlan: stripLeadingHashDisplay($("repas-id-plan").value),
        idRecette: idRecetteVal,
        type: $("repas-type").value.trim(),
        calories: caloriesVal,
      };
      if (typeof window !== "undefined" && window.NutriSmartSaisieCrud && window.NutriSmartSaisieCrudUi) {
        var feRepas = window.NutriSmartSaisieCrud.repasFieldErrors(payload);
        if (feRepas.length) {
          showMsg("Veuillez corriger les champs surlignés en rouge.", true);
          window.NutriSmartSaisieCrudUi.showFieldErrors(fr, feRepas);
          return;
        }
        window.NutriSmartSaisieCrudUi.clearFieldErrors(fr);
      }
      var resPromise;
      if (isRepasEditing) {
        var targetIds = editingGroupIds.length ? editingGroupIds.slice() : [editingRepasId];
        var recetteIds = persistedIds.length ? persistedIds.slice() : [idRecetteVal];
        // Mode édition robuste: remplacer entièrement le lot pour refléter exactement les recettes cochées.
        var chainReplace = Promise.resolve([]);
        targetIds.forEach(function (ridDelete) {
          chainReplace = chainReplace.then(function (acc) {
            return Promise.resolve(NutriSmartCRUD.repas.delete(ridDelete)).then(function (row) {
              acc.push(row);
              return acc;
            });
          });
        });
        recetteIds.forEach(function (recIdNew) {
          chainReplace = chainReplace.then(function (acc) {
            return Promise.resolve(
              NutriSmartCRUD.repas.create({
                idPlan: payload.idPlan,
                idRecette: recIdNew,
                type: payload.type,
                calories: payload.calories,
              })
            ).then(function (row) {
              acc.push(row);
              return acc;
            });
          });
        });
        resPromise = chainReplace;
      } else if (persistedIds.length > 1) {
        var chain = Promise.resolve([]);
        persistedIds.forEach(function (rid) {
          chain = chain.then(function (acc) {
            return Promise.resolve(
              NutriSmartCRUD.repas.create({
                idPlan: payload.idPlan,
                idRecette: rid,
                type: payload.type,
                calories: payload.calories,
              })
            ).then(function (row) {
              acc.push(row);
              return acc;
            });
          });
        });
        resPromise = chain;
      } else {
        resPromise = NutriSmartCRUD.repas.create(payload);
      }
      Promise.resolve(resPromise).then(function (res) {
        if (Array.isArray(res)) {
          var bad = res.find(function (x) { return x && x.error; });
          if (bad) {
            showMsg(bad.error, true);
            return;
          }
          if (window.NutriSmartSaisieCrudUi) window.NutriSmartSaisieCrudUi.clearFieldErrors(fr);
          showMsg(isRepasEditing ? "Repas mis à jour." : "Repas ajoutés.");
          resetForm("repas");
          hideCrudCompletelyAndNotify("form-repas", "Repas enregistres avec succes.");
          try { renderAll(); } catch (_e) {}
          return;
        }
        if (res && res.error) {
          var apiR =
            window.NutriSmartSaisieCrud && window.NutriSmartSaisieCrud.apiErrorsForRepas
              ? window.NutriSmartSaisieCrud.apiErrorsForRepas(res.error)
              : [];
          if (apiR.length && window.NutriSmartSaisieCrudUi) {
            showMsg("", false);
            window.NutriSmartSaisieCrudUi.showFieldErrors(fr, apiR);
          } else {
            showMsg(res.error, true);
          }
          return;
        }
        if (window.NutriSmartSaisieCrudUi) window.NutriSmartSaisieCrudUi.clearFieldErrors(fr);
        showMsg(isRepasEditing ? "Repas mis à jour." : "Repas ajouté.");
        resetForm("repas");
        hideCrudCompletelyAndNotify("form-repas", "Repas enregistre avec succes.");
        try { renderAll(); } catch (_e) {}
      });
    });

    var fprog = $("form-programmeSportif");
    if (fprog) fprog.addEventListener("submit", function (e) {
      e.preventDefault();
      var fixeAct = fixedActiviteFromPage();
      var typeSportVal = $("prog-type-sport") ? $("prog-type-sport").value.trim() : "";
      if (fixeAct) typeSportVal = fixeAct;
      var payload = {
        idPlan: stripLeadingHashDisplay($("prog-id-plan").value),
        typeSport: typeSportVal,
        niveau: $("prog-niveau").value.trim(),
        intensite: $("prog-intensite").value.trim(),
        dateSeance: $("prog-date-seance") ? $("prog-date-seance").value : "",
        dureeMin: $("prog-duree-min") ? $("prog-duree-min").value.trim() : "",
        statut: $("prog-statut-seance") ? $("prog-statut-seance").value.trim() : "",
      };
      if (typeof window !== "undefined" && window.NutriSmartSaisieCrud && window.NutriSmartSaisieCrudUi) {
        var feProg = window.NutriSmartSaisieCrud.programmeSportifFieldErrors(payload);
        if (feProg.length) {
          showMsg("Veuillez corriger les champs surlignés en rouge.", true);
          window.NutriSmartSaisieCrudUi.showFieldErrors(fprog, feProg);
          return;
        }
        window.NutriSmartSaisieCrudUi.clearFieldErrors(fprog);
      }
      var apiReadyForProgramme = !!(NutriSmartCRUD.isApi && NutriSmartCRUD.isApi());
      if (!apiReadyForProgramme) {
        if (NutriSmartCRUD.init && !fprog.getAttribute("data-api-retrying")) {
          fprog.setAttribute("data-api-retrying", "1");
          Promise.resolve(NutriSmartCRUD.init()).then(function (ok) {
            fprog.removeAttribute("data-api-retrying");
            if (ok) {
              if (typeof fprog.requestSubmit === "function") fprog.requestSubmit();
              else fprog.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
              return;
            }
            showMsg(
              "Enregistrement refusé : API MySQL indisponible. Le programme sportif n'est pas enregistre en backoffice.",
              true
            );
          }).catch(function () {
            fprog.removeAttribute("data-api-retrying");
            showMsg(
              "Enregistrement refusé : API MySQL indisponible. Le programme sportif n'est pas enregistre en backoffice.",
              true
            );
          });
          return;
        }
        showMsg(
          "Enregistrement refusé : API MySQL indisponible. Le programme sportif n'est pas enregistre en backoffice.",
          true
        );
        return;
      }
      if (!payload.statut) payload.statut = "prevue";
      var resPromise = editing.programmeSportif
        ? NutriSmartCRUD.programmeSportif.update(editing.programmeSportif, payload)
        : NutriSmartCRUD.programmeSportif.create(payload);
      Promise.resolve(resPromise).then(function (res) {
        if (res && res.error) {
          var apiP =
            window.NutriSmartSaisieCrud && window.NutriSmartSaisieCrud.apiErrorsForProgramme
              ? window.NutriSmartSaisieCrud.apiErrorsForProgramme(res.error)
              : [];
          if (apiP.length && window.NutriSmartSaisieCrudUi) {
            showMsg("", false);
            window.NutriSmartSaisieCrudUi.showFieldErrors(fprog, apiP);
          } else {
            showMsg(res.error, true);
          }
          return;
        }
        if (window.NutriSmartSaisieCrudUi) window.NutriSmartSaisieCrudUi.clearFieldErrors(fprog);
        var apiOn = NutriSmartCRUD.isApi && NutriSmartCRUD.isApi();
        var wasEditingProg = !!editing.programmeSportif;
        showMsg(wasEditingProg ? "Programme mis à jour." : "Programme créé.", false);
        resetForm("programmeSportif");
        renderAll();
        focusProgrammeTableIfPresent();
        hideCrudCompletelyAndNotify("form-programmeSportif", "Programme sportif enregistre avec succes.");
        try {
          if (typeof BroadcastChannel !== "undefined") {
            var _bcPub = new BroadcastChannel("nutrismart-crud-sync");
            _bcPub.postMessage({
              type: "programmeSportif",
              at: Date.now(),
              api: !!apiOn,
            });
            _bcPub.close();
          }
        } catch (_eBc) {}
        try {
          document.dispatchEvent(
            new CustomEvent("nutrismartProgrammeSportifSaved", {
              bubbles: true,
              detail: { row: res, api: !!apiOn },
            })
          );
        } catch (_eEv) {}
      });
    });

    var btnOpenMetierSport = $("btn-open-metier-sport");
    if (btnOpenMetierSport) {
      btnOpenMetierSport.addEventListener("click", function () {
        openMetierSportFromSelection();
      });
    }

    rootEl().querySelectorAll("[data-reset-form]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        resetForm(btn.getAttribute("data-reset-form"));
        showMsg("");
        if (typeof window !== "undefined" && window.NutriSmartSaisieCrudUi) {
          window.NutriSmartSaisieCrudUi.clearFieldErrors(rootEl());
        }
      });
    });

    var repasRecSel = $("repas-id-recette");
    if (repasRecSel) repasRecSel.addEventListener("change", onRepasRecetteSelectChange);

    function applyPlanRepasRowToForm(row) {
      if (!row) return;
      if ($("pr-id")) $("pr-id").value = row.id || "";
      if ($("pr-id-utilisateur")) $("pr-id-utilisateur").value = row.idUtilisateur || "";
      if ($("pr-date-debut")) $("pr-date-debut").value = row.dateDebut || "";
      if ($("pr-date-fin")) $("pr-date-fin").value = row.dateFin || "";
      ensureSelectValue("pr-objectif", row.objectif || "");
      ensureSelectValue("pr-statut", row.statut || "");
    }

    window.NutriSmartPlanRepasForm = {
      loadById: function (id) {
        var idStr = String(id == null ? "" : id).trim();
        if (!/^[1-9]\d*$/.test(idStr)) {
          showMsg("Indiquez un identifiant de plan valide (nombre entier positif).", true);
          return false;
        }
        var row = NutriSmartCRUD.planRepas.get(idStr);
        if (!row) {
          showMsg("Aucun plan repas trouvé pour l’ID " + idStr + ".", true);
          return false;
        }
        editing.planRepas = idStr;
        setFormVisible("planRepas", true);
        setEditingHint("planRepas", idStr);
        applyPlanRepasRowToForm(row);
        syncFormEditingState();
        var fp = $("form-planRepas");
        if (fp && window.NutriSmartSaisieCrudUi) window.NutriSmartSaisieCrudUi.clearFieldErrors(fp);
        showMsg("Plan chargé : modifiez puis cliquez sur « Mettre à jour ».", false);
        return true;
      },
      deleteById: function (id) {
        var idStr = String(id == null ? "" : id).trim();
        if (!/^[1-9]\d*$/.test(idStr)) {
          showMsg("Indiquez un identifiant de plan valide.", true);
          return Promise.resolve({ error: "id" });
        }
        return Promise.resolve(NutriSmartCRUD.planRepas.delete(idStr)).then(function (delRes) {
          if (delRes && delRes.error) {
            showMsg(delRes.error, true);
            return delRes;
          }
          showMsg("Plan repas n° " + idStr + " supprimé.");
          if (String(editing.planRepas) === idStr) resetForm("planRepas");
          renderAll();
          return delRes;
        });
      },
      getEditingId: function () {
        return editing.planRepas;
      },
    };

  });
})();
