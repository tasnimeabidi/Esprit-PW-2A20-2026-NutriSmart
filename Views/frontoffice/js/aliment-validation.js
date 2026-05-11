document.addEventListener("DOMContentLoaded", function () {
  const forms = document.querySelectorAll('form');
  // On filtre pour ne garder que les formulaires qui gèrent des aliments



  forms.forEach(form => {
    const nomInput = form.querySelector('input[name="nom_aliment"]');
    if (!nomInput) return;

    form.addEventListener("submit", function (e) {
      let isValid = true;
      let errors = [];

      const nomValue = nomInput.value.trim();

      // 1. Lettres uniquement (Alphabet)
      const alphaRegex = /^[a-zA-ZÀ-ÿ\s]+$/;

      if (nomValue.length <= 3) {
        isValid = false;
        errors.push("Le nom doit avoir plus de 3 caractères (minimum 4).");
      } else if (!alphaRegex.test(nomValue)) {
        isValid = false;
        errors.push("Le nom ne doit contenir que des lettres.");
      }

      // 2. Nombres positifs
      const numbers = form.querySelectorAll('input[type="number"]');
      numbers.forEach(input => {
        if (input.value !== "" && parseFloat(input.value) < 0) {
          isValid = false;
          errors.push(input.placeholder + " doit être un nombre positif.");
        }
      });

      if (!isValid) {
        e.preventDefault();
        alert(errors.join("\n"));
      }
    });
  });
});
