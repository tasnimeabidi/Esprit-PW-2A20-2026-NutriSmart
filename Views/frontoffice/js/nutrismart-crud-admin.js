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

  function showMsg(text, isErr) {
    var el = $("crud-global-msg");
    if (!el) return;
    el.textContent = text || "";
    el.className = MSG_CLASS + (isErr ? " " + MSG_CLASS + "--err" : "");
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
    if (!tb) return;
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
    rows.forEach(function (r) {
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
        r.id +
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
    rows.forEach(function (r) {
      var tr = document.createElement("tr");
      tr.innerHTML =
        "<td>" +
        r.id +
        "</td><td>" +
        escapeHtml(r.idPlan) +
        "</td><td>" +
        escapeHtml(recetteNomAffiche(r.idRecette)) +
        "</td><td>" +
        escapeHtml(r.type) +
        "</td><td>" +
        escapeHtml(caloriesAffichePourRepas(r)) +
        '</td><td class="bo-crud-td-actions">' +
        actionButtonsHtml(r.id) +
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
    rows.forEach(function (r) {
      var tr = document.createElement("tr");
      tr.innerHTML =
        "<td>" +
        r.id +
        "</td><td>" +
        escapeHtml(r.idPlan) +
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
        actionButtonsHtml(r.id) +
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

  function recetteNomAffiche(idRec) {
    var idStr = idRec == null ? "" : String(idRec).trim();
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
          return "#" + p.id + " — " + (p.objectif || "sans titre");
        },
        "Choisir un plan"
      );
      var recs =
        NutriSmartCRUD.recettes && NutriSmartCRUD.recettes.list
          ? NutriSmartCRUD.recettes.list()
          : [];
      fillSelect(
        $("repas-id-recette"),
        recs,
        "id",
        function (x) {
          return x.nom || "#" + x.id;
        },
        "— Aucune recette —",
        function (it, o) {
          var raw = it.caloriesTotales != null ? it.caloriesTotales : it.calories_totales;
          var ct = raw != null ? String(raw).trim() : "";
          if (ct !== "") o.setAttribute("data-calories", ct);
        }
      );
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
          return "#" + p.id + " — " + (p.objectif || "sans titre");
        },
        "Choisir un plan"
      );
    }
  }

  var LEGACY_SELECT_IDS = {
    programmeSportif: ["prog-type-sport", "prog-niveau", "prog-intensite", "prog-statut-seance"],
    planRepas: ["pr-statut"],
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

  /** Recette avec calories_totales → champ calories en lecture seule, style « CALORIES ». */
  function applyRepasCaloriesReadonlyUi() {
    var sel = $("repas-id-recette");
    var inp = $("repas-calories");
    if (!sel || !inp) return;
    var dc = caloriesPourRecetteSelectionnee(sel);
    var val = String(inp.value).trim();
    if (dc !== "") {
      if (val === "" || val === dc) {
        if (val === "") inp.value = dc;
        inp.readOnly = true;
        inp.classList.add("ns-repas-calories-field--from-recette");
      } else {
        inp.readOnly = false;
        inp.classList.remove("ns-repas-calories-field--from-recette");
      }
    } else {
      inp.readOnly = false;
      inp.classList.remove("ns-repas-calories-field--from-recette");
    }
    if (
      typeof window !== "undefined" &&
      window.NutriSmartSaisieCrudUi &&
      window.NutriSmartSaisieCrudUi.refreshRepasCaloriesFieldSuccessUi
    ) {
      window.NutriSmartSaisieCrudUi.refreshRepasCaloriesFieldSuccessUi(inp);
    }
  }

  function onRepasRecetteSelectChange() {
    var sel = $("repas-id-recette");
    var inp = $("repas-calories");
    if (!sel || !inp) return;
    var dc = caloriesPourRecetteSelectionnee(sel);
    if (dc !== "") {
      inp.value = dc;
      inp.readOnly = true;
      inp.classList.add("ns-repas-calories-field--from-recette");
    } else {
      inp.readOnly = false;
      inp.classList.remove("ns-repas-calories-field--from-recette");
      if (!sel.value || sel.value === "") inp.value = "";
    }
    if (
      typeof window !== "undefined" &&
      window.NutriSmartSaisieCrudUi &&
      window.NutriSmartSaisieCrudUi.refreshRepasCaloriesFieldSuccessUi
    ) {
      window.NutriSmartSaisieCrudUi.refreshRepasCaloriesFieldSuccessUi(inp);
    }
  }

  function resetForm(name) {
    editing[name] = null;
    stripLegacySelectsForForm(name);
    var form = $("form-" + name);
    if (form) {
      form.reset();
      if (typeof window !== "undefined" && window.NutriSmartSaisieCrudUi) {
        window.NutriSmartSaisieCrudUi.clearFieldErrors(form);
      }
    }
    if (name === "repas") {
      var ci = $("repas-calories");
      if (ci) {
        ci.readOnly = false;
        ci.classList.remove("ns-repas-calories-field--from-recette");
      }
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
      if (act === "del") {
        if (
          !confirm(
            "Supprimer définitivement l’enregistrement n° " + id + " ? Cette action ne peut pas être annulée."
          )
        )
          return;
        Promise.resolve(NutriSmartCRUD[entity].delete(id)).then(function (delRes) {
          if (delRes && delRes.error) {
            showMsg(delRes.error, true);
            return;
          }
          showMsg("Supprimé.");
          onRefresh();
          renderAll();
        });
        return;
      }
      if (act === "edit") {
        var row = NutriSmartCRUD[entity].get(id);
        if (!row) return;
        editing[entity] = id;
        setEditingHint(entity, id);
        if (entity === "planRepas") {
          $("pr-id-utilisateur").value = row.idUtilisateur || "";
          $("pr-date-debut").value = row.dateDebut || "";
          $("pr-date-fin").value = row.dateFin || "";
          var o0 = row.objectif || "";
          $("pr-objectif").value =
            window.NutriSmartSaisieCrud && window.NutriSmartSaisieCrud.sanitizePlanObjectifLetters
              ? window.NutriSmartSaisieCrud.sanitizePlanObjectifLetters(o0)
              : o0;
          ensureSelectValue("pr-statut", row.statut || "");
        } else if (entity === "repas") {
          $("repas-id-plan").value = row.idPlan || "";
          ensureSelectValue("repas-id-recette", row.idRecette || "");
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
          $("prog-id-plan").value = row.idPlan || "";
          var fixeAct =
            typeof document !== "undefined" && document.documentElement.getAttribute("data-ns-activite-fixe");
          fixeAct = fixeAct ? String(fixeAct).trim() : "";
          ensureSelectValue("prog-type-sport", fixeAct || row.typeSport || "");
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
    }

    rootEl().querySelectorAll(TAB_SEL).forEach(function (btn) {
      btn.addEventListener("click", function () {
        switchTab(btn.getAttribute("data-tab"));
      });
    });

    if (entityEnabled("planRepas")) bindTableActions("tbody-plan-repas", "planRepas", renderAll);
    if (entityEnabled("repas")) bindTableActions("tbody-repas", "repas", renderAll);
    if (entityEnabled("programmeSportif")) bindTableActions("tbody-programme", "programmeSportif", renderAll);

    if (typeof window !== "undefined" && window.NutriSmartSaisieCrudUi) {
      ["form-planRepas", "form-repas", "form-programmeSportif"].forEach(function (fid) {
        var f = $(fid);
        if (f) window.NutriSmartSaisieCrudUi.attachLiveClear(f);
      });
    }

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
      });
    });

    var fr = $("form-repas");
    if (fr) fr.addEventListener("submit", function (e) {
      e.preventDefault();
      var idRecetteVal = $("repas-id-recette").value.trim();
      var caloriesVal = $("repas-calories").value.trim();
      if (caloriesVal === "" && idRecetteVal !== "") {
        var fromRec = getCaloriesTotalesPourRecetteId(idRecetteVal);
        if (fromRec !== "") caloriesVal = fromRec;
      }
      var payload = {
        idPlan: $("repas-id-plan").value,
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
      var resPromise = editing.repas
        ? NutriSmartCRUD.repas.update(editing.repas, payload)
        : NutriSmartCRUD.repas.create(payload);
      Promise.resolve(resPromise).then(function (res) {
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
        showMsg(editing.repas ? "Repas mis à jour." : "Repas ajouté.");
        resetForm("repas");
        renderAll();
      });
    });

    var fprog = $("form-programmeSportif");
    if (fprog) fprog.addEventListener("submit", function (e) {
      e.preventDefault();
      var fixeAct =
        typeof document !== "undefined" && document.documentElement.getAttribute("data-ns-activite-fixe");
      fixeAct = fixeAct ? String(fixeAct).trim() : "";
      var typeSportVal = $("prog-type-sport") ? $("prog-type-sport").value.trim() : "";
      if (fixeAct) typeSportVal = fixeAct;
      var payload = {
        idPlan: $("prog-id-plan").value,
        typeSport: typeSportVal,
        niveau: $("prog-niveau").value.trim(),
        intensite: $("prog-intensite").value.trim(),
        dateSeance: $("prog-date-seance") ? $("prog-date-seance").value : "",
        dureeMin: $("prog-duree-min") ? $("prog-duree-min").value.trim() : "",
        statut: $("prog-statut-seance") ? $("prog-statut-seance").value.trim() || "prevue" : "prevue",
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
        showMsg(editing.programmeSportif ? "Programme mis à jour." : "Programme créé.");
        resetForm("programmeSportif");
        renderAll();
      });
    });

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
      if ($("pr-id-utilisateur")) $("pr-id-utilisateur").value = row.idUtilisateur || "";
      if ($("pr-date-debut")) $("pr-date-debut").value = row.dateDebut || "";
      if ($("pr-date-fin")) $("pr-date-fin").value = row.dateFin || "";
      var o0 = row.objectif || "";
      if ($("pr-objectif")) {
        $("pr-objectif").value =
          typeof window !== "undefined" &&
          window.NutriSmartSaisieCrud &&
          window.NutriSmartSaisieCrud.sanitizePlanObjectifLetters
            ? window.NutriSmartSaisieCrud.sanitizePlanObjectifLetters(o0)
            : o0;
      }
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
