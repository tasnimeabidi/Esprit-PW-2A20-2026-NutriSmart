(function (global) {
  'use strict';

  var DISALLOWED_LOG = ['voiture', 'car', 'avion', 'plane'];
  var MIN_DESC_LEN = 3;
  /** Aligné sur chat_handler.php (CHAT_MAX_MSG_LEN) */
  var MAX_CHAT_LEN = 2000;

  function trim(s) {
    return String(s == null ? '' : s).trim();
  }

  function email(value) {
    var v = trim(value);
    if (!v) return { ok: false, msg: "L'adresse e-mail est requise." };
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return { ok: false, msg: "Format d'e-mail invalide." };
    return { ok: true };
  }

  function passwordLogin(value) {
    if (trim(value) === '') return { ok: false, msg: 'Identifiants requis.' };
    return { ok: true };
  }

  /**
   * @param {string} text
   * @param {{ maxLen?: number }} [opts]
   */
  function chatMessage(text, opts) {
    var maxLen = opts && opts.maxLen ? opts.maxLen : MAX_CHAT_LEN;
    var v = trim(text);
    if (!v) return { ok: false, msg: 'Message vide.' };
    if (v.length > maxLen) return { ok: false, msg: 'Message trop long (max ' + maxLen + ' caractères).' };
    return { ok: true };
  }

  /**
   * @param {string} description
   * @param {'meal'|'activity'|string|null|undefined} type
   */
  function logDescription(description, type) {
    var v = trim(description);
    if (type !== 'meal' && type !== 'activity') return { ok: true };
    if (v.length < MIN_DESC_LEN) {
      return { ok: false, msg: 'La description doit contenir au moins 3 caractères.' };
    }
    var lower = v.toLowerCase();
    for (var i = 0; i < DISALLOWED_LOG.length; i++) {
      if (lower.indexOf(DISALLOWED_LOG[i]) !== -1) {
        return { ok: false, msg: "Veuillez entrer un aliment ou une activité valide." };
      }
    }
    return { ok: true };
  }

  function contactNom(value) {
    var v = trim(value);
    if (v.length < 3) return { ok: false, msg: 'Le nom doit contenir au moins 3 caractères.' };
    return { ok: true };
  }

  function contactSujet(value) {
    var v = trim(value);
    if (v.length < 3) return { ok: false, msg: 'Le sujet doit contenir au moins 3 caractères.' };
    return { ok: true };
  }

  function contactMessage(value) {
    var v = trim(value);
    if (v.length < 10) return { ok: false, msg: 'Le message doit contenir au moins 10 caractères.' };
    return { ok: true };
  }

  function weightKg(raw) {
    var s = String(raw == null ? '' : raw).trim().replace(',', '.');
    var n = parseFloat(s);
    if (isNaN(n) || n <= 0 || n > 500) return { ok: false, msg: 'Entrez un poids réaliste (entre 1 et 500 kg).' };
    return { ok: true, value: n };
  }

  global.NutriSmartValidate = {
    trim: trim,
    email: email,
    passwordLogin: passwordLogin,
    chatMessage: chatMessage,
    logDescription: logDescription,
    contactNom: contactNom,
    contactSujet: contactSujet,
    contactMessage: contactMessage,
    weightKg: weightKg,
    MAX_CHAT_MESSAGE_LEN: MAX_CHAT_LEN,
    DISALLOWED_LOG_WORDS: DISALLOWED_LOG.slice()
  };
})(typeof window !== 'undefined' ? window : this);
