/**
 * NutriSmart — script frontend (sans backend)
 * Interception des formulaires : ajoute une ligne au tableau en mémoire page uniquement.
 * Les données ne sont pas persistées (rechargement = retour aux lignes d'exemple).
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    // Formulaire présent uniquement sur les pages entité (pas sur index.html)
    var form = document.getElementById("entity-form");
    var tbody = document.getElementById("entity-tbody");

    if (!form || !tbody) {
      return;
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      var tr = document.createElement("tr");
      var inputs = form.querySelectorAll(
        "input:not([type='hidden']), select, textarea"
      );

      inputs.forEach(function (el) {
        var td = document.createElement("td");
        td.textContent = el.value !== "" ? el.value : "—";
        tr.appendChild(td);
      });

      tbody.appendChild(tr);
      form.reset();
    });
  });
})();
