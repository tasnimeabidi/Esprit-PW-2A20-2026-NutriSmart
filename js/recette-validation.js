document.addEventListener("DOMContentLoaded", function () {
  const forms = document.querySelectorAll('form');
  // On filtre pour ne garder que les formulaires qui gèrent des recettes

  forms.forEach(form => {
    const nomRecetteInput = form.querySelector('input[name="nom_recette"]');
    if (!nomRecetteInput) return;

    form.addEventListener("submit", function (e) {
      let isValid = true;
      let errors = [];

      const nomRecetteValue = nomRecetteInput.value.trim();
      const instructionsInput = form.querySelector('textarea[name="instructions"]');
      const instructionsValue = instructionsInput ? instructionsInput.value.trim() : "";

      // 1. Validation du nom de recette : Lettres uniquement (Alphabet) et longueur > 4
      const alphaRegex = /^[a-zA-ZÀ-ÿ\s]+$/;

      if (nomRecetteValue.length <= 4) {
        isValid = false;
        errors.push("Le nom de la recette doit avoir plus de 4 caractères (minimum 5).");
      } else if (!alphaRegex.test(nomRecetteValue)) {
        isValid = false;
        errors.push("Le nom de la recette ne doit contenir que des lettres.");
      }

      // 2. Validation des instructions de préparation
      if (instructionsInput && instructionsValue.length <= 10) {
        isValid = false;
        errors.push("Les instructions de préparation doivent contenir au moins 10 caractères.");
      }

      // 3. Nombres positifs (calories, temps de préparation, etc.)
      const numbers = form.querySelectorAll('input[type="number"]');
      numbers.forEach(input => {
        if (input.value !== "" && parseFloat(input.value) < 0) {
          isValid = false;
          const labelElement = input.closest('.form-field') ? input.closest('.form-field').querySelector('label') : null;
          const label = labelElement ? labelElement.textContent.replace('*', '').trim() : (input.placeholder || input.name);
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
