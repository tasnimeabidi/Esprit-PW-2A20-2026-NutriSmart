/**
 * NutriSmart — Gestion simple des formulaires (frontend)
 * Ajout dans localStorage + affichage tableau
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    
    var form = document.getElementById("entity-form");
    var tbody = document.getElementById("entity-tbody");

    if (!form || !tbody) return;

    var STORAGE_KEY = form.getAttribute("data-entity") || "data";

    // Charger les données existantes
    function loadData() {
      try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
      } catch {
        return [];
      }
    }

    // Sauvegarder
    function saveData(data) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    // Affichage
    function render() {
      var data = loadData();
      tbody.innerHTML = "";

      data.forEach(function (row) {
        var tr = document.createElement("tr");

        Object.values(row).forEach(function (val) {
          var td = document.createElement("td");
          td.textContent = val || "—";
          tr.appendChild(td);
        });

        tbody.appendChild(tr);
      });
    }

    // Ajouter
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      var data = loadData();
      var obj = {};

      var inputs = form.querySelectorAll("input, select, textarea");

      inputs.forEach(function (el) {
        if (!el.name) return;
        obj[el.name] = el.value.trim();
      });

      // Validation simple (humaine)
      if (!obj.nom && !obj.type && !obj.objectif) {
        alert("Veuillez remplir les champs obligatoires.");
        return;
      }

      data.push(obj);
      saveData(data);

      render();
      form.reset();
    });

    // Initial render
    render();
  });

})();