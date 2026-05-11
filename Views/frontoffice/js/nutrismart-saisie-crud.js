/**
 * Validation/UX CRUD (cadres rouge/vert + message sous champ).
 */
(function () {
  "use strict";

  function byId(id) {
    return typeof document !== "undefined" ? document.getElementById(id) : null;
  }

  function normalizeSpaces(s) {
    return String(s || "").replace(/\s+/g, " ").trim();
  }

  function sanitizePlanObjectifLetters(v) {
    return String(v || "")
      .replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s'-]/g, "")
      .replace(/\s+/g, " ")
      .trim();
  }

  function fieldNode(form, field) {
    if (!form) return null;
    return (
      form.querySelector("#" + field) ||
      form.querySelector('[name="' + field + '"]') ||
      form.querySelector('[name="' + field.replace(/[A-Z]/g, function (m) { return "_" + m.toLowerCase(); }) + '"]')
    );
  }

  function ensureMsgNodes(input) {
    var label = input ? input.closest("label") : null;
    if (!label) return { err: null, ok: null };
    var err = label.querySelector(".ns-saisie-field-msg");
    var ok = label.querySelector(".ns-saisie-success-msg");
    if (!err) {
      err = document.createElement("span");
      err.className = "ns-saisie-field-msg";
      err.setAttribute("role", "alert");
      label.appendChild(err);
    }
    if (!ok) {
      ok = document.createElement("span");
      ok.className = "ns-saisie-success-msg";
      ok.setAttribute("aria-live", "polite");
      label.appendChild(ok);
    }
    return { err: err, ok: ok };
  }

  function clearNodeState(input) {
    if (!input) return;
    input.classList.remove("ns-saisie-input-invalid");
    input.classList.remove("ns-saisie-input-valid");
    var m = ensureMsgNodes(input);
    if (m.err) {
      m.err.textContent = "";
      m.err.classList.remove("is-visible");
    }
    if (m.ok) {
      m.ok.textContent = "";
      m.ok.classList.remove("is-active");
      m.ok.classList.remove("is-visible");
    }
  }

  function markInvalid(input, message) {
    if (!input) return;
    var m = ensureMsgNodes(input);
    input.classList.remove("ns-saisie-input-valid");
    input.classList.add("ns-saisie-input-invalid");
    if (m.ok) {
      m.ok.textContent = "";
      m.ok.classList.remove("is-active");
      m.ok.classList.remove("is-visible");
    }
    if (m.err) {
      m.err.textContent = message || "";
      if (m.err.textContent) m.err.classList.add("is-visible");
      else m.err.classList.remove("is-visible");
    }
  }

  function markValid(input) {
    if (!input) return;
    var m = ensureMsgNodes(input);
    input.classList.remove("ns-saisie-input-invalid");
    input.classList.add("ns-saisie-input-valid");
    if (m.err) {
      m.err.textContent = "";
      m.err.classList.remove("is-visible");
    }
    if (m.ok) {
      m.ok.textContent = "";
      m.ok.classList.add("is-active");
      m.ok.classList.add("is-visible");
    }
  }

  function clearFieldErrors(scope) {
    if (!scope || !scope.querySelectorAll) return;
    scope.querySelectorAll(".ns-saisie-input-invalid, .ns-saisie-input-valid").forEach(function (el) {
      el.classList.remove("ns-saisie-input-invalid");
      el.classList.remove("ns-saisie-input-valid");
    });
    scope.querySelectorAll(".ns-saisie-field-msg").forEach(function (n) {
      n.textContent = "";
      n.classList.remove("is-visible");
    });
    scope.querySelectorAll(".ns-saisie-success-msg").forEach(function (n) {
      n.textContent = "";
      n.classList.remove("is-active");
      n.classList.remove("is-visible");
    });
  }

  function showFieldErrors(form, errors) {
    if (!form) return;
    clearFieldErrors(form);
    var byField = {};
    (errors || []).forEach(function (e) {
      if (e && e.field) byField[e.field] = e.message || "Champ invalide.";
    });

    var inputs = form.querySelectorAll("input, select, textarea");
    inputs.forEach(function (input) {
      if (input.type === "checkbox") return;
      var key = input.id || input.name || "";
      key = key.replace(/^pr-/, "").replace(/^repas-/, "").replace(/^prog-/, "");
      var map = {
        idutilisateur: "idUtilisateur",
        datedebut: "dateDebut",
        datefin: "dateFin",
        idplan: "idPlan",
        idrecette: "idRecette",
        type: "type",
        typesport: "typeSport",
        dateseance: "dateSeance",
        niveau: "niveau",
        intensite: "intensite",
        dureemin: "dureeMin",
        statutseance: "statut",
        calories: "calories",
      };
      var norm = key.replace(/[-_]/g, "").toLowerCase();
      var field = map[norm] || key;
      if (byField[field]) markInvalid(input, byField[field]);
      else clearNodeState(input);
    });
  }

  function attachLiveClear(form) {
    if (!form) return;
    form.querySelectorAll("input, select, textarea").forEach(function (input) {
      ensureMsgNodes(input);
      var keyLower = String(input.id || input.name || "").toLowerCase();
      if (keyLower.indexOf("calories") !== -1) {
        input.addEventListener("blur", function () {
          var rawVal = String(input.value || "");
          var cleaned = rawVal.replace(/\D+/g, "");
          if (input.value !== cleaned) input.value = cleaned;
          if (cleaned === "") clearNodeState(input);
          else markValid(input);
        });
      }
      input.addEventListener("input", function () {
        var key = String(input.id || input.name || "").toLowerCase();
        if (key.indexOf("calories") !== -1) {
          var rawVal = String(input.value || "");
          var hadInvalidChars = /\D/.test(rawVal);
          if (hadInvalidChars) {
            markInvalid(input, "Saisissez uniquement des chiffres (les lettres ne sont pas autorisées).");
          } else if (rawVal.trim() !== "") {
            markValid(input);
          } else {
            clearNodeState(input);
          }
          return;
        }
        if (key.indexOf("duree") !== -1) {
          var rawDur = String(input.value || "");
          if (rawDur.trim() === "") {
            clearNodeState(input);
          } else if (!/^\d+$/.test(rawDur) || Number(rawDur) < 1) {
            markInvalid(input, "Saisissez une durée en minutes (nombre positif).");
          } else {
            markValid(input);
          }
          return;
        }
        if (input.classList.contains("ns-saisie-input-invalid")) {
          var v = normalizeSpaces(input.value);
          if (v !== "") {
            input.classList.remove("ns-saisie-input-invalid");
            markValid(input);
          } else {
            clearNodeState(input);
          }
        } else if (normalizeSpaces(input.value) !== "") {
          markValid(input);
        } else {
          clearNodeState(input);
        }
      });
      input.addEventListener("change", function () {
        var key = String(input.id || input.name || "").toLowerCase();
        if (key.indexOf("calories") !== -1) {
          var rawValC = String(input.value || "");
          var cleanedC = rawValC.replace(/\D+/g, "");
          if (input.value !== cleanedC) input.value = cleanedC;
          if (cleanedC === "") clearNodeState(input);
          else markValid(input);
          return;
        }
        if (key.indexOf("duree") !== -1) {
          var rawDurC = String(input.value || "");
          if (rawDurC.trim() === "") {
            clearNodeState(input);
          } else if (!/^\d+$/.test(rawDurC) || Number(rawDurC) < 1) {
            markInvalid(input, "Saisissez une durée en minutes (nombre positif).");
          } else {
            markValid(input);
          }
          return;
        }
        var v = normalizeSpaces(input.value);
        if (v !== "") markValid(input);
        else clearNodeState(input);
      });
    });
  }

  function refreshRepasCaloriesFieldSuccessUi(input) {
    if (!input) return;
    if (normalizeSpaces(input.value) !== "") markValid(input);
    else clearNodeState(input);
  }

  function planRepasFieldErrors(payload) {
    var out = [];
    var minPlanIso = "2026-04-01";
    var idU = normalizeSpaces(payload && payload.idUtilisateur);
    var d1 = normalizeSpaces(payload && payload.dateDebut);
    var d2 = normalizeSpaces(payload && payload.dateFin);
    var obj = normalizeSpaces(payload && payload.objectif);
    if (!/^\d+$/.test(idU) || Number(idU) < 1) out.push({ field: "idUtilisateur", message: "ID utilisateur invalide." });
    if (!d1) out.push({ field: "dateDebut", message: "La date de début est obligatoire." });
    if (!d2) out.push({ field: "dateFin", message: "La date de fin est obligatoire." });
    if (d1 && d1 < minPlanIso) {
      out.push({ field: "dateDebut", message: "La date de début doit être à partir d'avril 2026." });
    }
    if (d2 && d2 < minPlanIso) {
      out.push({ field: "dateFin", message: "La date de fin doit être à partir d'avril 2026." });
    }
    var isoD = /^\d{4}-\d{2}-\d{2}$/;
    if (d1 && d2 && isoD.test(d1) && isoD.test(d2) && d2 < d1) {
      out.push({
        field: "dateFin",
        message: "La date de fin ne peut pas précéder la date de début.",
      });
    }
    if (!obj) out.push({ field: "objectif", message: "Choisissez un objectif dans la liste." });
    return out;
  }

  function repasFieldErrors(payload) {
    var out = [];
    var idP = normalizeSpaces(payload && payload.idPlan);
    var idR = normalizeSpaces(payload && payload.idRecette);
    var type = normalizeSpaces(payload && payload.type);
    var cal = normalizeSpaces(payload && payload.calories);
    if (!/^\d+$/.test(idP) || Number(idP) < 1) {
      out.push({ field: "idPlan", message: "Selectionnez un plan." });
    }
    if (!/^\d+$/.test(idR) || Number(idR) < 1) {
      out.push({ field: "idRecette", message: "Selectionnez au moins une recette." });
    }
    if (!type) {
      out.push({ field: "type", message: "Le type est obligatoire." });
    }
    if (!cal) {
      out.push({ field: "calories", message: "Les calories sont obligatoires." });
    }
    if (cal && !/^\d+$/.test(cal)) out.push({ field: "calories", message: "Calories doit etre numerique." });
    return out;
  }

  function programmeSportifFieldErrors(payload) {
    var out = [];
    var idP = normalizeSpaces(payload && payload.idPlan);
    var type = normalizeSpaces(payload && payload.typeSport);
    var niveau = normalizeSpaces(payload && payload.niveau);
    var intensite = normalizeSpaces(payload && payload.intensite);
    var dateS = normalizeSpaces(payload && payload.dateSeance);
    var dmin = normalizeSpaces(payload && payload.dureeMin);
    var statut = normalizeSpaces(payload && payload.statut);
    if (!/^\d+$/.test(idP) || Number(idP) < 1) out.push({ field: "idPlan", message: "Selectionnez un plan." });
    if (!type) out.push({ field: "typeSport", message: "Le type sport est obligatoire." });
    if (!niveau) out.push({ field: "niveau", message: "Le niveau est obligatoire." });
    if (!intensite) out.push({ field: "intensite", message: "L'intensite est obligatoire." });
    if (!dateS) out.push({ field: "dateSeance", message: "La date seance est obligatoire." });
    if (!/^\d+$/.test(dmin) || Number(dmin) < 1) out.push({ field: "dureeMin", message: "La duree doit etre un nombre positif." });
    if (!statut) out.push({ field: "statut", message: "Le statut de la seance est obligatoire." });
    return out;
  }

  function apiErr(raw, defaultField) {
    var msg = normalizeSpaces(raw);
    if (!msg) return [];
    return [{ field: defaultField, message: msg }];
  }

  window.NutriSmartSaisieCrud = {
    sanitizePlanObjectifLetters: sanitizePlanObjectifLetters,
    planRepasFieldErrors: planRepasFieldErrors,
    repasFieldErrors: repasFieldErrors,
    programmeSportifFieldErrors: programmeSportifFieldErrors,
    apiErrorsForPlanRepas: function (m) { return apiErr(m, "objectif"); },
    apiErrorsForRepas: function (m) {
      var msg = normalizeSpaces(m).toLowerCase();
      if (msg.indexOf("recette") !== -1) return apiErr(m, "idRecette");
      if (msg.indexOf("calorie") !== -1) return apiErr(m, "calories");
      return apiErr(m, "type");
    },
    apiErrorsForProgramme: function (m) {
      var msg = normalizeSpaces(m).toLowerCase();
      if (msg.indexOf("duree") !== -1 || msg.indexOf("minute") !== -1 || msg.indexOf("24 h") !== -1) {
        return apiErr(m, "dureeMin");
      }
      if (msg.indexOf("date") !== -1) return apiErr(m, "dateSeance");
      if (msg.indexOf("intens") !== -1) return apiErr(m, "intensite");
      if (msg.indexOf("niveau") !== -1) return apiErr(m, "niveau");
      if (msg.indexOf("statut") !== -1) return apiErr(m, "statut");
      if (msg.indexOf("plan") !== -1) return apiErr(m, "idPlan");
      return apiErr(m, "typeSport");
    },
  };

  window.NutriSmartSaisieCrudUi = {
    clearFieldErrors: clearFieldErrors,
    showFieldErrors: showFieldErrors,
    attachLiveClear: attachLiveClear,
    refreshRepasCaloriesFieldSuccessUi: refreshRepasCaloriesFieldSuccessUi,
  };

  document.addEventListener("DOMContentLoaded", function () {
    ["form-planRepas", "form-repas", "form-programmeSportif"].forEach(function (id) {
      var f = byId(id);
      if (f) attachLiveClear(f);
    });
  });
})();
