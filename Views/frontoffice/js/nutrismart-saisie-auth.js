/**
 * Auth front (inscription, connexion, profil) — validation visuelle alignée nutrismart-saisie-ui.css
 * (cadre rouge / vert + message sous chaque champ).
 */
(function () {
  "use strict";

  var STORAGE_USERS = "nutrismart_auth_users_v1";
  var STORAGE_SESSION = "nutrismart_auth_session_v1";

  function norm(s) {
    return String(s || "").replace(/\s+/g, " ").trim();
  }

  function letterCount(s) {
    var m = String(s || "").match(/[A-Za-zÀ-ÖØ-öø-ÿ]/g);
    return m ? m.length : 0;
  }

  function isEmailOk(s) {
    var t = norm(s);
    if (!t) return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t);
  }

  function ensureAuthMsgNodes(group) {
    if (!group) return { err: null, ok: null };
    var err = group.querySelector(".ns-saisie-field-msg");
    var ok = group.querySelector(".ns-saisie-success-msg");
    if (!err) {
      err = document.createElement("span");
      err.className = "ns-saisie-field-msg";
      err.setAttribute("role", "alert");
      group.appendChild(err);
    }
    if (!ok) {
      ok = document.createElement("span");
      ok.className = "ns-saisie-success-msg";
      ok.setAttribute("aria-live", "polite");
      group.appendChild(ok);
    }
    return { err: err, ok: ok };
  }

  function fieldGroup(el) {
    return el ? el.closest(".form-group") : null;
  }

  function clearFieldState(input) {
    if (!input) return;
    input.classList.remove("ns-saisie-input-invalid");
    input.classList.remove("ns-saisie-input-valid");
    var g = fieldGroup(input);
    var m = ensureAuthMsgNodes(g);
    if (m.err) m.err.textContent = "";
    if (m.ok) {
      m.ok.textContent = "";
      m.ok.classList.remove("is-active");
    }
  }

  function markInvalid(input, message) {
    if (!input) return;
    var g = fieldGroup(input);
    var m = ensureAuthMsgNodes(g);
    input.classList.remove("ns-saisie-input-valid");
    input.classList.add("ns-saisie-input-invalid");
    if (m.ok) {
      m.ok.textContent = "";
      m.ok.classList.remove("is-active");
    }
    if (m.err) m.err.textContent = message || "";
  }

  function markValid(input) {
    if (!input) return;
    var g = fieldGroup(input);
    var m = ensureAuthMsgNodes(g);
    input.classList.remove("ns-saisie-input-invalid");
    input.classList.add("ns-saisie-input-valid");
    if (m.err) m.err.textContent = "";
    if (m.ok) {
      m.ok.textContent = "";
      m.ok.classList.add("is-active");
    }
  }

  function clearFormFieldsVisual(form) {
    if (!form) return;
    form.querySelectorAll("input, select, textarea").forEach(function (inp) {
      clearFieldState(inp);
    });
  }

  function readUsers() {
    try {
      var raw = localStorage.getItem(STORAGE_USERS);
      var arr = raw ? JSON.parse(raw) : [];
      return Array.isArray(arr) ? arr : [];
    } catch (_e) {
      return [];
    }
  }

  function writeUsers(arr) {
    try {
      localStorage.setItem(STORAGE_USERS, JSON.stringify(arr));
    } catch (_e) {}
  }

  function errNom(nom) {
    if (letterCount(nom) < 3) return "Le nom doit contenir au moins 3 lettres.";
    return "";
  }

  function errEmail(email) {
    var t = norm(email);
    if (!t) return "L’adresse e-mail est obligatoire.";
    if (!isEmailOk(t)) return "Format d’e-mail invalide.";
    return "";
  }

  function errPassword(pw) {
    if (norm(pw).length < 6) return "Le mot de passe doit contenir au moins 6 caractères.";
    return "";
  }

  function errIdUser(idu) {
    var t = norm(idu);
    if (!t) return "";
    if (!/^\d+$/.test(t)) return "L’identifiant utilisateur doit être un nombre.";
    return "";
  }

  function validateRegisterFields(form, idUser, nom, email, password, applyUi) {
    var idIn = form ? form.querySelector("#reg-id-user") : document.getElementById("reg-id-user");
    var nomIn = form ? form.querySelector("#reg-nom") : document.getElementById("reg-nom");
    var emIn = form ? form.querySelector("#reg-email") : document.getElementById("reg-email");
    var pwIn = form ? form.querySelector("#reg-password") : document.getElementById("reg-password");

    var eId = errIdUser(idUser);
    var eNom = errNom(nom);
    var eEm = errEmail(email);
    var ePw = errPassword(password);

    if (applyUi) {
      if (idIn) {
        if (eId) markInvalid(idIn, eId);
        else if (norm(idUser) !== "") markValid(idIn);
        else clearFieldState(idIn);
      }
      if (nomIn) {
        if (eNom) markInvalid(nomIn, eNom);
        else if (norm(nom) !== "") markValid(nomIn);
        else clearFieldState(nomIn);
      }
      if (emIn) {
        if (eEm) markInvalid(emIn, eEm);
        else if (norm(email) !== "") markValid(emIn);
        else clearFieldState(emIn);
      }
      if (pwIn) {
        if (ePw) markInvalid(pwIn, ePw);
        else if (norm(password) !== "") markValid(pwIn);
        else clearFieldState(pwIn);
      }
    }

    if (eId) return eId;
    if (eNom) return eNom;
    if (eEm) return eEm;
    if (ePw) return ePw;
    return "";
  }

  function validateRegisterSingleInput(input) {
    if (!input || !input.id) return;
    var id = input.id;
    var v = input.value;
    if (id === "reg-id-user") {
      var e = errIdUser(v);
      if (e) markInvalid(input, e);
      else if (norm(v) !== "") markValid(input);
      else clearFieldState(input);
      return;
    }
    if (id === "reg-nom") {
      var en = errNom(v);
      if (en) markInvalid(input, en);
      else if (norm(v) !== "") markValid(input);
      else clearFieldState(input);
      return;
    }
    if (id === "reg-email") {
      var ee = errEmail(v);
      if (ee) markInvalid(input, ee);
      else if (norm(v) !== "") markValid(input);
      else clearFieldState(input);
      return;
    }
    if (id === "reg-password") {
      var ep = errPassword(v);
      if (ep) markInvalid(input, ep);
      else if (norm(v) !== "") markValid(input);
      else clearFieldState(input);
    }
  }

  function attachLiveRegister(form) {
    if (!form || form.id !== "form-register") return;
    form.querySelectorAll("input").forEach(function (inp) {
      ["input", "blur"].forEach(function (ev) {
        inp.addEventListener(ev, function () {
          validateRegisterSingleInput(inp);
        });
      });
    });
  }

  function attachLiveLogin(form) {
    if (!form || form.id !== "form-login") return;
    var em = form.querySelector("#login-email");
    var pw = form.querySelector("#login-password");
    function run() {
      if (em) {
        if (norm(em.value) === "") clearFieldState(em);
        else if (!isEmailOk(em.value)) markInvalid(em, "Format d’e-mail invalide.");
        else markValid(em);
      }
      if (pw) {
        if (norm(pw.value) === "") clearFieldState(pw);
        else markValid(pw);
      }
    }
    if (em) {
      em.addEventListener("input", run);
      em.addEventListener("blur", run);
    }
    if (pw) {
      pw.addEventListener("input", run);
      pw.addEventListener("blur", run);
    }
  }

  function errAge(a) {
    var t = norm(a);
    if (!t) return "L’âge est obligatoire.";
    var n = parseInt(t, 10);
    if (String(n) !== t || n < 10 || n > 120) return "Indiquez un âge entre 10 et 120 ans.";
    return "";
  }

  function errPoids(p) {
    var t = norm(p).replace(",", ".");
    if (!t) return "Le poids est obligatoire.";
    var x = parseFloat(t);
    if (!isFinite(x) || x <= 0 || x > 500) return "Indiquez un poids réaliste (kg).";
    return "";
  }

  function errTaille(ta) {
    var t = norm(ta);
    if (!t) return "La taille est obligatoire.";
    var n = parseInt(t, 10);
    if (String(n) !== t || n < 80 || n > 250) return "Indiquez une taille entre 80 et 250 cm.";
    return "";
  }

  function validateProfilFields(form, age, poids, taille, applyUi) {
    var aIn = form ? form.querySelector("#profil-age") : null;
    var pIn = form ? form.querySelector("#profil-poids") : null;
    var tIn = form ? form.querySelector("#profil-taille") : null;
    var eA = errAge(age);
    var eP = errPoids(poids);
    var eT = errTaille(taille);
    if (applyUi) {
      if (aIn) {
        if (eA) markInvalid(aIn, eA);
        else if (norm(age) !== "") markValid(aIn);
        else clearFieldState(aIn);
      }
      if (pIn) {
        if (eP) markInvalid(pIn, eP);
        else if (norm(poids) !== "") markValid(pIn);
        else clearFieldState(pIn);
      }
      if (tIn) {
        if (eT) markInvalid(tIn, eT);
        else if (norm(taille) !== "") markValid(tIn);
        else clearFieldState(tIn);
      }
    }
    if (eA) return eA;
    if (eP) return eP;
    if (eT) return eT;
    return "";
  }

  function validateProfilSingleInput(input) {
    if (!input || !input.id) return;
    if (input.id === "profil-age") {
      var e = errAge(input.value);
      if (e) markInvalid(input, e);
      else if (norm(input.value) !== "") markValid(input);
      else clearFieldState(input);
      return;
    }
    if (input.id === "profil-poids") {
      var ep = errPoids(input.value);
      if (ep) markInvalid(input, ep);
      else if (norm(input.value) !== "") markValid(input);
      else clearFieldState(input);
      return;
    }
    if (input.id === "profil-taille") {
      var et = errTaille(input.value);
      if (et) markInvalid(input, et);
      else if (norm(input.value) !== "") markValid(input);
      else clearFieldState(input);
    }
  }

  function attachLiveProfil(form) {
    if (!form || form.id !== "form-profil") return;
    ["#profil-age", "#profil-poids", "#profil-taille"].forEach(function (sel) {
      var inp = form.querySelector(sel);
      if (!inp) return;
      ["input", "blur"].forEach(function (ev) {
        inp.addEventListener(ev, function () {
          validateProfilSingleInput(inp);
        });
      });
    });
  }

  function afficherErreur(form, msg) {
    if (!form) return;
    var id = form.getAttribute("data-error-id");
    if (!id) return;
    var el = document.getElementById(id);
    if (!el) {
      el = document.createElement("p");
      el.id = id;
      el.className = "auth-form-global-msg";
      el.setAttribute("role", "alert");
      form.insertBefore(el, form.firstChild);
    }
    el.textContent = msg || "";
    el.hidden = !msg;
  }

  function register(idUser, nom, email, password) {
    var form = document.getElementById("form-register");
    var msg = validateRegisterFields(form, idUser, nom, email, password, true);
    if (msg) return "Veuillez corriger les champs surlignés en rouge.";

    var users = readUsers();
    var em = norm(email).toLowerCase();
    if (users.some(function (u) { return norm(u.email).toLowerCase() === em; })) {
      var emIn = form && form.querySelector("#reg-email");
      if (emIn) markInvalid(emIn, "Cette adresse e-mail est déjà utilisée.");
      return "Cette adresse e-mail est déjà utilisée.";
    }

    users.push({
      idUser: norm(idUser) || String(Date.now()),
      nom: norm(nom),
      email: norm(email),
      password: String(password || ""),
    });
    writeUsers(users);
    try {
      localStorage.setItem(
        STORAGE_SESSION,
        JSON.stringify({ email: norm(email), nom: norm(nom) })
      );
    } catch (_e) {}
    clearFormFieldsVisual(form);
    return "";
  }

  function login(email, password) {
    var form = document.getElementById("form-login");
    var emIn = form && form.querySelector("#login-email");
    var pwIn = form && form.querySelector("#login-password");
    var eEm = errEmail(email);
    if (norm(password) === "") {
      if (pwIn) markInvalid(pwIn, "Le mot de passe est obligatoire.");
      if (emIn && !eEm && norm(email) !== "") markValid(emIn);
      else if (emIn && eEm) markInvalid(emIn, eEm);
      else if (emIn) clearFieldState(emIn);
      return "Veuillez corriger les champs surlignés en rouge.";
    }
    if (eEm) {
      if (emIn) markInvalid(emIn, eEm);
      if (pwIn) markValid(pwIn);
      return "Veuillez corriger les champs surlignés en rouge.";
    }
    if (emIn) markValid(emIn);
    if (pwIn) markValid(pwIn);

    var users = readUsers();
    var em = norm(email).toLowerCase();
    var u = users.find(function (x) {
      return norm(x.email).toLowerCase() === em && String(x.password) === String(password);
    });
    if (!u) {
      if (emIn) markInvalid(emIn, "E-mail ou mot de passe incorrect.");
      if (pwIn) markInvalid(pwIn, "E-mail ou mot de passe incorrect.");
      return "E-mail ou mot de passe incorrect.";
    }
    try {
      localStorage.setItem(STORAGE_SESSION, JSON.stringify({ email: u.email, nom: u.nom }));
    } catch (_e2) {}
    clearFormFieldsVisual(form);
    return "";
  }

  function profil(age, poids, taille) {
    var form = document.getElementById("form-profil");
    var msg = validateProfilFields(form, age, poids, taille, true);
    if (msg) return "Veuillez corriger les champs surlignés en rouge.";
    try {
      localStorage.setItem(
        "nutrismart_profil_v1",
        JSON.stringify({
          age: norm(age),
          poids: norm(poids).replace(",", "."),
          taille: norm(taille),
        })
      );
    } catch (_e) {}
    clearFormFieldsVisual(form);
    return "";
  }

  document.addEventListener("DOMContentLoaded", function () {
    attachLiveRegister(document.getElementById("form-register"));
    attachLiveLogin(document.getElementById("form-login"));
    attachLiveProfil(document.getElementById("form-profil"));
  });

  window.NutriSmartSaisieAuth = {
    register: register,
    login: login,
    profil: profil,
    afficherErreur: afficherErreur,
    clearFormFieldsVisual: clearFormFieldsVisual,
    markInvalid: markInvalid,
    markValid: markValid,
    clearFieldState: clearFieldState,
  };
})();
