document.addEventListener("DOMContentLoaded", () => {
  // --- Configuration ---
  const config = {
    colors: {
      success: "#3dba52",
      error: "#e53935",
      successBg: "rgba(61, 186, 82, 0.1)",
      errorBg: "rgba(229, 57, 53, 0.1)",
    },
  };

  // --- Validators ---
  const validators = {
    nom: (val) => /^[a-zA-ZÀ-ÿ\s'-]{3,5}$/.test(val.trim()),
    email: (val) => /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(val.trim()),
    password: (val) => /^(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/.test(val),
    login_password: (val) => val.trim().length > 0,
    age: (val) => {
      const v = parseInt(val);
      return !isNaN(v) && v >= 14 && v <= 70;
    },
    poids: (val) => {
      const v = parseFloat(val);
      return !isNaN(v) && v >= 30 && v <= 300;
    },
    taille: (val) => {
      const v = parseInt(val);
      return !isNaN(v) && v >= 130 && v <= 250;
    },
    message: (val) => val.trim().length >= 10,
  };

  const errorMessages = {
    nom: "Le nom doit contenir au moins 3 lettres.",
    email: "Adresse e-mail invalide.",
    password: "Min. 8 caractères, 1 majuscule, 1 chiffre.",
    login_password: "Le mot de passe est requis.",
    age: "Âge requis ",
    poids: "Poids requis ",
    taille: "Taille requise",
    message: "Le message doit contenir au moins 10 caractères.",
  };

  // --- Core Functions ---

  const updateUI = (input, isValid, message) => {
    const parent = input.parentNode;

    // Apply styles
    input.style.borderColor = isValid
      ? config.colors.success
      : config.colors.error;
    input.style.boxShadow = `0 0 0 4px ${isValid ? config.colors.successBg : config.colors.errorBg
      }`;
    input.style.transition = "all 0.3s ease";

    // Handle Error Message
    let errorDiv = parent.querySelector(".validation-msg");
    if (!errorDiv) {
      errorDiv = document.createElement("div");
      errorDiv.className = "validation-msg";
      errorDiv.style.fontSize = "0.78rem";
      errorDiv.style.marginTop = "0.4rem";
      errorDiv.style.fontWeight = "600";
      parent.appendChild(errorDiv);
    }

    if (isValid) {
      errorDiv.innerText = "✓ Valide";
      errorDiv.style.color = config.colors.success;
    } else {
      errorDiv.innerText = message;
      errorDiv.style.color = config.colors.error;
    }
  };

  const validateInput = (input) => {
    const name = input.getAttribute("name");
    const id = input.getAttribute("id");
    const value = input.value;

    // Determine which validator to use
    let validatorKey = name;
    if (id === "login_password") validatorKey = "login_password";
    if (id === "edit_password" && value === "") return true; // Optional password in edit

    if (validators[validatorKey]) {
      const isValid = validators[validatorKey](value);
      updateUI(input, isValid, errorMessages[validatorKey]);
      return isValid;
    }
    return true; // No validator defined, skip
  };

  const initForm = (formId) => {
    const form = document.getElementById(formId);
    if (!form) return;

    const inputs = form.querySelectorAll(
      'input:not([type="hidden"]), textarea, select'
    );

    inputs.forEach((input) => {
      // Immediate feedback on input
      input.addEventListener("input", () => validateInput(input));
      // Also on blur for completeness
      input.addEventListener("blur", () => validateInput(input));
    });

    form.addEventListener("submit", (e) => {
      let isFormValid = true;
      inputs.forEach((input) => {
        if (!validateInput(input)) {
          isFormValid = false;
        }
      });

      if (!isFormValid) {
        e.preventDefault();
        // Scroll to first error
        const firstError = form.querySelector(
          '.validation-msg[style*="rgb(229, 57, 53)"]'
        );
        if (firstError)
          firstError.parentNode.scrollIntoView({
            behavior: "smooth",
            block: "center",
          });
      }
    });
  };

  // --- Initialize Forms ---
  const formsToValidate = [
    "registerForm",
    "loginForm",
    "profileForm",
    "contactForm",
    "addForm",
    "editForm",
  ];

  formsToValidate.forEach((id) => initForm(id));
});
