/**
 * Page Repas : cartes « Repas complets » → ouverture de la modale CRUD (#repas-crud-dialog).
 */
(function () {
  "use strict";

  function getDialog() {
    return document.getElementById("repas-crud-dialog");
  }

  function openRepasCrudModal() {
    var dlg = getDialog();
    if (!dlg || typeof dlg.showModal !== "function") return;
    if (!dlg.open) dlg.showModal();
  }

  function closeRepasCrudModal() {
    var dlg = getDialog();
    if (dlg && dlg.open) dlg.close();
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.body.addEventListener("click", function (e) {
      var card = e.target.closest(".js-repas-open-crud");
      if (!card) return;
      e.preventDefault();
      openRepasCrudModal();
    });

    document.body.addEventListener("keydown", function (e) {
      if (e.key !== "Enter" && e.key !== " ") return;
      var card = e.target.closest(".js-repas-open-crud");
      if (!card) return;
      e.preventDefault();
      openRepasCrudModal();
    });

    document.querySelectorAll("[data-repas-crud-close]").forEach(function (btn) {
      btn.addEventListener("click", closeRepasCrudModal);
    });
  });
})();
