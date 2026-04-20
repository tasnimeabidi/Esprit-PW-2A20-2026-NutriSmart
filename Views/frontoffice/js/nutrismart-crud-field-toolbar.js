/**
 * Stylo : focus / calendrier / sélection ; corbeille : vider le champ.
 * Tout formulaire .bo-crud-form avec .pr-field-btn[data-pr-target="idDuChamp"].
 */
(function () {
  "use strict";

  document.addEventListener("click", function (e) {
    var btn = e.target.closest(".pr-field-btn[data-pr-target]");
    if (!btn) return;
    var form = btn.closest("form.bo-crud-form");
    if (!form || !form.contains(btn)) return;
    var fid = btn.getAttribute("data-pr-target");
    if (!fid) return;
    var el = document.getElementById(fid);
    if (!el) return;
    e.preventDefault();

    if (btn.classList.contains("pr-field-btn--edit")) {
      el.focus();
      if (typeof el.showPicker === "function" && el.type === "date") {
        try {
          el.showPicker();
        } catch (err) {
          /* navigateurs sans showPicker */
        }
      }
      if (el.tagName === "SELECT") {
        try {
          el.focus();
        } catch (e2) {}
      }
      if ((el.type === "text" || !el.type) && typeof el.select === "function") {
        try {
          el.select();
        } catch (e3) {}
      }
      return;
    }

    if (btn.classList.contains("pr-field-btn--clear")) {
      if (el.tagName === "SELECT") {
        el.selectedIndex = 0;
      } else {
        el.value = "";
      }
      el.dispatchEvent(new Event("input", { bubbles: true }));
      el.dispatchEvent(new Event("change", { bubbles: true }));
    }
  });
})();
