document.addEventListener("DOMContentLoaded", function () {
  const forms = document.querySelectorAll('form');

  forms.forEach(form => {
    const nomAlimentInput = form.querySelector('input[name="nom_aliment"]');
    if (!nomAlimentInput) return;

    form.addEventListener("submit", function (e) {
      let isValid = true;
      let errors = [];

      const nomAlimentValue = nomAlimentInput.value.trim();
      const categorieSelect = form.querySelector('select[name="categorie"]');
      const categorieValue = categorieSelect ? categorieSelect.value : "";

      // 1. Validation du nom de l'aliment : Lettres uniquement et longueur > 4
      const alphaRegex = /^[a-zA-ZÀ-ÿ\s]+$/;

      if (nomAlimentValue.length <= 4) {
        isValid = false;
        errors.push("Le nom de l'aliment doit avoir plus de 4 caractères.");
      } else if (!alphaRegex.test(nomAlimentValue)) {
        isValid = false;
        errors.push("Le nom de l'aliment ne doit contenir que des lettres.");
      }

      // 2. Validation de la catégorie
      if (categorieSelect && categorieValue === "") {
        isValid = false;
        errors.push("Veuillez choisir une catégorie pour l'aliment.");
      }

      // 3. Nombres positifs pour les valeurs nutritionnelles et le prix
      const numberInputs = form.querySelectorAll('input[type="number"]');
      numberInputs.forEach(input => {
        if (input.value !== "" && parseFloat(input.value) < 0) {
          isValid = false;
          const label = input.closest('.form-field').querySelector('label').textContent.replace('*', '').trim();
          errors.push(label + " doit être un nombre positif.");
        }
      });

      if (!isValid) {
        e.preventDefault();
        alert(errors.join("\n"));
      }
    });
  });
});
