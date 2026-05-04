/**
 * Cartes jour (Lundi… Dimanche) → ouverture de la modale CRUD plan_repas.
 */
(function () {
  "use strict";

  function getDialog() {
    return document.getElementById("plan-repas-crud-dialog");
  }

  function openPlanCrudModal() {
    var dlg = getDialog();
    if (!dlg || typeof dlg.showModal !== "function") return;
    if (!dlg.open) dlg.showModal();
    var form = dlg.querySelector("#form-planRepas");
    if (form && window.NutriSmartSaisieCrudUi && typeof window.NutriSmartSaisieCrudUi.clearFieldErrors === "function") {
      window.NutriSmartSaisieCrudUi.clearFieldErrors(form);
    }
    var scroll = dlg.querySelector(".pr-crud-scroll");
    if (scroll) scroll.scrollTop = 0;
  }

  function closePlanCrudModal() {
    var dlg = getDialog();
    if (dlg && dlg.open) dlg.close();
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.body.addEventListener("click", function (e) {
      var card = e.target.closest(".js-open-plan-crud");
      if (!card) return;
      e.preventDefault();
      openPlanCrudModal();
    });

    document.body.addEventListener("keydown", function (e) {
      if (e.key !== "Enter" && e.key !== " ") return;
      var card = e.target.closest(".js-open-plan-crud");
      if (!card) return;
      e.preventDefault();
      openPlanCrudModal();
    });

    document.querySelectorAll("[data-plan-crud-close]").forEach(function (btn) {
      btn.addEventListener("click", closePlanCrudModal);
    });

    var grid = document.querySelector(".days-grid--plan");
    var addDayBtn = document.getElementById("btn-add-extra-day");
    var extraDayCount = 0;
    if (addDayBtn && grid) {
      addDayBtn.addEventListener("click", function () {
        extraDayCount += 1;
        var last = grid.querySelector("article.day-card:last-of-type");
        if (!last) return;
        var clone = last.cloneNode(true);
        clone.classList.remove("is-current");
        var nameEl = clone.querySelector(".day-name");
        if (nameEl) nameEl.textContent = "Jour " + (7 + extraDayCount);
        var pill = clone.querySelector(".kcal-pill");
        if (pill) pill.textContent = "— kcal";
        grid.appendChild(clone);
      });
    }
  });
})();
