document.addEventListener("DOMContentLoaded", () => {
  // Disable native HTML validation for a premium custom experience
  document.querySelectorAll('form').forEach(form => {
    form.setAttribute('novalidate', true);
    form.reset();
  });
  
  // Aggressive clear after 500ms to fight browser persistent autofill
  setTimeout(() => {
    document.querySelectorAll('input').forEach(input => {
      if (input.type !== 'submit' && input.type !== 'button' && input.type !== 'checkbox' && input.type !== 'radio') {
        input.value = '';
      }
    });
  }, 500);

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
    nom: (val) => /^[a-zA-ZÀ-ÿ\s'-]{3,10}$/.test(val.trim()),
    email: (val) => /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(val.trim()),
    password: (val) => val.trim().length > 0,
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
    password: "Mot de passe requis.",
    login_password: "Le mot de passe est requis.",
    age: "Âge requis ",
    poids: "Poids requis ",
    taille: "Taille requise",
    message: "Le message doit contenir au moins 10 caractères.",
  };

  const updateUI = (input, isValid, message) => {
    const parent = input.parentNode;
    input.style.borderColor = isValid ? config.colors.success : config.colors.error;
    let msg = parent.querySelector(".validation-msg");
    if (!msg) {
      msg = document.createElement("div");
      msg.className = "validation-msg";
      msg.style.cssText = "font-size:0.75rem; margin-top:0.3rem; font-weight:500;";
      parent.appendChild(msg);
    }
    msg.textContent = isValid ? "" : message;
    msg.style.color = isValid ? config.colors.success : config.colors.error;
  };

  const validateInput = (input) => {
    const name = input.getAttribute("name");
    const val = input.value;
    if (validators[name]) {
      const isValid = validators[name](val);
      updateUI(input, isValid, errorMessages[name]);
      return isValid;
    }
    return true;
  };

  // Attach input listeners
  document.querySelectorAll('form input, form textarea').forEach(input => {
    input.addEventListener("input", () => validateInput(input));
    input.addEventListener("blur", () => validateInput(input));
  });

  // --- AGGRESSIVE GLOBAL SUBMIT INTERCEPTOR ---
  document.addEventListener("submit", (e) => {
    const form = e.target;
    const formId = form.id;
    const ajaxForms = ['loginForm', 'modalLoginForm', 'registerForm', 'profileForm'];

    if (ajaxForms.includes(formId)) {
      e.preventDefault();
      e.stopPropagation();

      console.log("Interception AJAX du formulaire:", formId);

      // Handle Error Containers based on form ID
        let errorContainer, errorText, successContainer, successText;
      if (formId === 'loginForm') {
        errorContainer = document.getElementById('login-error-container');
        errorText = document.getElementById('login-error-text');
      } else if (formId === 'modalLoginForm') {
        errorContainer = document.getElementById('modal-login-error');
        errorText = document.getElementById('modal-error-text');
      } else if (formId === 'registerForm') {
        errorContainer = document.getElementById('register-error-container');
        errorText = document.getElementById('register-error-text');
        successContainer = document.getElementById('register-success-container');
        successText = document.getElementById('register-success-text');
      }

      if (errorContainer) errorContainer.style.display = 'none';
      if (typeof successContainer !== 'undefined' && successContainer) successContainer.style.display = 'none';

      // LOADING STATE
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

      // --- NEW: PRE-SUBMIT VALIDATION ---
      let isFormValid = true;
      form.querySelectorAll('input, textarea').forEach(input => {
        if (validators[input.name]) {
          if (!validateInput(input)) isFormValid = false;
        }
      });

      if (!isFormValid) {
        form.classList.add('shake-anim');
        setTimeout(() => form.classList.remove('shake-anim'), 500);
        return;
      }

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Envoi...';
      }
      const action = form.getAttribute('action');
      const formData = new FormData(form);

      fetch(action, {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
          }

          if (data.success) {
            if (formId === 'registerForm' && typeof successContainer !== 'undefined' && successContainer) {
              successText.textContent = data.message;
              successContainer.style.display = 'flex';
              form.style.display = 'none'; // Cacher le formulaire pour bien voir le message
              if (errorContainer) errorContainer.style.display = 'none';
              // Wait a bit before redirecting if redirect is present
              if (data.redirect) {
                setTimeout(() => { window.location.href = data.redirect; }, 3000);
              }
            } else {
              window.location.href = data.redirect || 'nutrismart-home.html';
            }
          } else {
            // Error: Show message and highlight inputs
            if (errorContainer && errorText) {
              errorText.innerText = data.message || 'Une erreur est survenue.';
              errorContainer.style.display = 'flex';
            } else {
              alert(data.message);
            }

            // --- SECURITY: Disable button if too many attempts ---
            const msg = (data.message || '').toLowerCase();
            if (msg.includes('tentatives') || msg.includes('suspendu')) {
              const submitBtn = form.querySelector('button[type="submit"]');
              if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
                submitBtn.textContent = 'Accès Blâmé';
              }
            }

            // Highlight basic text/email/password inputs in red
            form.querySelectorAll('input:not([type="checkbox"]):not([type="radio"])').forEach(input => {
              input.style.borderColor = config.colors.error;
            });

            form.classList.remove('shake-anim');
            void form.offsetWidth;
            form.classList.add('shake-anim');
          }
        })
        .catch(err => {
          console.error('Fetch Error:', err);
          alert("Erreur réseau ou réponse invalide du serveur.");
        });
    }
  });

  // Re-enable button and CLEAR styles when typing (smart refresh)
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('input', (e) => {
      const submitBtn = form.querySelector('button[type="submit"]');
      
      // 1. Reset button if it was disabled/blocked
      if (submitBtn && (submitBtn.disabled || submitBtn.textContent === 'Accès Blâmé' || submitBtn.style.opacity === '0.5')) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
        if (form.id === 'loginForm' || form.id === 'modalLoginForm') submitBtn.textContent = 'Se connecter';
        else if (form.id === 'registerForm') submitBtn.textContent = "S'inscrire";
      }

      // 2. Clear error message containers
      const errorContainers = [
        document.getElementById('login-error-container'),
        document.getElementById('modal-login-error'),
        document.getElementById('register-error-container'),
        document.getElementById('register-success-container'),
        document.querySelector('.error-container') // generic
      ];
      errorContainers.forEach(c => { if(c) c.style.display = 'none'; });

      // 3. Reset input borders
      form.querySelectorAll('input').forEach(input => {
        input.style.borderColor = ''; // return to CSS default
      });
    });
  });

  // --- Global Functions ---
  window.togglePW = (id) => {
    const input = document.getElementById(id);
    const btn = input.nextElementSibling;
    if (input.type === 'password') {
      input.type = 'text';
      btn.classList.add('showing-pw');
    } else {
      input.type = 'password';
      btn.classList.remove('showing-pw');
    }
  };

  // Modal control functions moved to HTML for onclick reliability

  // handleResetSubmit moved to HTML for onclick reliability

  // Adaptive Navbar & Profile logic
  fetch('api.php?action=session')
    .then(res => res.json())
    .then(data => {
      if (data.loggedIn) {
        // --- Populate Profile Page Badges if present ---
        const profileName = document.getElementById('profile-user-name');
        const profileEmail = document.getElementById('profile-user-email');
        if (profileName) profileName.textContent = `👤 ${data.name}`;
        if (profileEmail) profileEmail.textContent = `📧 ${data.email}`;

        const isAdmin = data.role && data.role.toLowerCase().trim() === 'admin';
        if (isAdmin) {
          const userNav = document.getElementById('user-nav-auth');
          const publicNav = document.getElementById('public-nav-auth');
          if (userNav) userNav.style.display = 'none';
          if (publicNav) publicNav.style.display = 'flex';
        } else if (window.location.pathname.includes('nutrismart-website.html')) {
          window.location.href = 'nutrismart-home.html';
        }
      }
    })
    .catch(() => { });

  // Dropdown Logic
  const dropdownToggle = document.getElementById('dropdownToggle');
  const userDropdown = document.getElementById('userDropdown');
  if (dropdownToggle && userDropdown) {
    dropdownToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      userDropdown.classList.toggle('show');
    });
    window.addEventListener('click', () => {
      if (userDropdown && userDropdown.classList.contains('show')) userDropdown.classList.remove('show');
    });
  }

  // --- URL Message Handler (for Email Verification, etc.) ---
  const urlParams = new URLSearchParams(window.location.search);
  const successMsg = urlParams.get('success');
  const errorMsg = urlParams.get('error');

  if (successMsg) {
    const successContainer = document.getElementById('login-success-container');
    const successText = document.getElementById('login-success-text');
    if (successContainer && successText) {
      successText.textContent = successMsg;
      successContainer.style.display = 'flex';
    }
  }

  if (errorMsg) {
    // Check login page
    const loginErrorContainer = document.getElementById('login-error-container');
    const loginErrorText = document.getElementById('login-error-text');
    if (loginErrorContainer && loginErrorText) {
      loginErrorText.textContent = errorMsg;
      loginErrorContainer.style.display = 'flex';
    }
    // Check register page
    const registerErrorContainer = document.getElementById('register-error-container');
    const registerErrorText = document.getElementById('register-error-text');
    if (registerErrorContainer && registerErrorText) {
      registerErrorText.textContent = errorMsg;
      registerErrorContainer.style.display = 'flex';
    }
  }
});
