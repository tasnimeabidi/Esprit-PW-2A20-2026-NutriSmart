/**
 * Validation temps réel (états visuels) — formulaire PlanRepas (page plan-repas-crud).
 * Classes : nutrismart-saisie-ui.css (.ns-saisie-input-invalid, .ns-saisie-input-valid, etc.)
 */
(function () {
  "use strict";

  var FORM_ID = "form-planRepas";

  function getWrapLabel(input) {
    if (!input) return null;
    if (input.id) {
      var byFor = document.querySelector('label[for="' + input.id + '"]');
      if (byFor) return byFor;
    }
    return input.closest("label");
  }

  function ensureMsgSpans(label) {
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

  function setState(input, opts) {
    var invalid = opts.invalid;
    var errText = opts.errText || "";
    var valid = opts.valid && !invalid;
    var label = getWrapLabel(input);
    var msgs = ensureMsgSpans(label);

    input.classList.toggle("ns-saisie-input-invalid", !!invalid);
    input.classList.toggle("ns-saisie-input-valid", !!valid);

    if (msgs.err) {
      msgs.err.textContent = errText;
      msgs.err.classList.toggle("is-visible", !!(invalid && errText));
    }
    if (msgs.ok) {
      msgs.ok.classList.toggle("is-visible", !!valid);
    }
  }

  function validateId(v) {
    if (!v || !String(v).trim()) return { ok: false, msg: "Indiquez un identifiant numérique." };
    if (!/^\d+$/.test(String(v).trim())) return { ok: false, msg: "Utilisez uniquement des chiffres." };
    return { ok: true };
  }

  function validateDate(v) {
    if (!v) return { ok: false, msg: "Choisissez une date." };
    return { ok: true };
  }

  function validateObjectif(v) {
    var t = String(v || "").trim();
    if (t.length < 1) return { ok: false, msg: "Saisissez un objectif." };
    /* Lettres uniquement (espaces autorisés entre les mots). */
    if (!/^[a-zA-ZàâäéèêëïîôùûüçÀÂÄÉÈÊËÏÎÔÙÛÜÇ\s]+$/.test(t)) {
      return { ok: false, msg: "L’objectif ne doit contenir que des lettres (espaces autorisés)." };
    }
    var lettersOnly = t.replace(/\s/g, "");
    if (lettersOnly.length < 3) return { ok: false, msg: "Au moins 3 lettres." };
    return { ok: true };
  }

  function validateStatut(v) {
    if (v === "" || v == null) return { ok: false, msg: "Sélectionnez un statut." };
    return { ok: true };
  }

  function compareDates(debut, fin) {
    if (!debut || !fin) return { ok: true };
    var a = new Date(debut);
    var b = new Date(fin);
    if (isNaN(a.getTime()) || isNaN(b.getTime())) return { ok: true };
    if (b < a) return { ok: false, msg: "La date de fin doit être après la date de début." };
    return { ok: true };
  }

  function runField(input, touched) {
    if (!input || !input.name) return;
    if (input.type === "hidden") return;
    var name = input.name;
    var v = input.value;

    if (name === "idUtilisateur") {
      var r = validateId(v);
      setState(input, { invalid: touched && !r.ok, errText: r.msg || "", valid: touched && r.ok });
      return;
    }
    if (name === "dateDebut") {
      var d1 = validateDate(v);
      var finEl = document.getElementById("pr-date-fin");
      var cross = finEl && finEl.value ? compareDates(v, finEl.value) : { ok: true };
      if (!d1.ok) {
        setState(input, { invalid: touched && true, errText: d1.msg, valid: false });
      } else if (!cross.ok) {
        setState(input, { invalid: touched && true, errText: cross.msg, valid: false });
      } else {
        setState(input, { invalid: false, errText: "", valid: touched && true });
      }
      if (finEl && finEl.value) runField(finEl, true);
      return;
    }
    if (name === "dateFin") {
      var d2 = validateDate(v);
      var debEl = document.getElementById("pr-date-debut");
      var cross2 = debEl && debEl.value ? compareDates(debEl.value, v) : { ok: true };
      if (!d2.ok) {
        setState(input, { invalid: touched && true, errText: d2.msg, valid: false });
      } else if (!cross2.ok) {
        setState(input, { invalid: touched && true, errText: cross2.msg, valid: false });
      } else {
        setState(input, { invalid: false, errText: "", valid: touched && true });
      }
      return;
    }
    if (name === "objectif") {
      var o = validateObjectif(v);
      setState(input, { invalid: touched && !o.ok, errText: o.msg || "", valid: touched && o.ok });
      return;
    }
    if (name === "statut") {
      var s = validateStatut(v);
      setState(input, { invalid: touched && !s.ok, errText: s.msg || "", valid: touched && s.ok });
    }
  }

  function init() {
    var form = document.getElementById(FORM_ID);
    if (!form || !document.getElementById("plan-repas-crud-root")) return;

    var inputs = form.querySelectorAll("input:not([type=\"hidden\"]), select");
    inputs.forEach(function (input) {
      ensureMsgSpans(getWrapLabel(input));

      var touched = false;
      input.addEventListener("blur", function () {
        touched = true;
        runField(input, true);
      });
      input.addEventListener("input", function () {
        if (input.id === "pr-objectif") {
          var raw = input.value;
          var cleaned = raw.replace(/[^a-zA-ZàâäéèêëïîôùûüçÀÂÄÉÈÊËÏÎÔÙÛÜÇ\s]/g, "");
          if (cleaned !== raw) input.value = cleaned;
        }
        runField(input, touched);
      });
      input.addEventListener("change", function () {
        touched = true;
        runField(input, true);
      });
    });

    /** Blocage de l’envoi si erreurs (phase capture : avant nutrismart-crud-admin.js). */
    form.addEventListener(
      "submit",
      function (e) {
        var fields = form.querySelectorAll("input:not([type='hidden']), select");
        var allOk = true;
        fields.forEach(function (input) {
          runField(input, true);
          if (input.classList.contains("ns-saisie-input-invalid")) allOk = false;
        });
        if (!allOk) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          var msgEl = document.getElementById("crud-global-msg");
          if (msgEl) {
            msgEl.textContent = "Veuillez corriger les champs invalides avant d’enregistrer.";
            msgEl.className = "bo-crud-msg bo-crud-msg--err";
          }
        }
      },
      true
    );
  }

  document.addEventListener("DOMContentLoaded", function () {
    init();
  });
})();
