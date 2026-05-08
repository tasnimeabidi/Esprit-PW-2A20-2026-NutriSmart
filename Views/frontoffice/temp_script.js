

    // --- MODAL CONTROL FUNCTIONS ---
    window.openModal = function () {
      const modal = document.getElementById('loginModal');
      if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => {
          modal.classList.add('active');
        }, 10);
        document.body.style.overflow = 'hidden';
      }
    };

    window.closeModal = function () {
      const modal = document.getElementById('loginModal');
      if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
          modal.style.display = 'none';
        }, 300);
        document.body.style.overflow = '';
      }
    };

    window.openResetModal = function () {
      window.closeModal();
      const modal = document.getElementById('resetModal');
      if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);

        // Pre-fill email if possible
        const loginEmail = document.querySelector('#modalLoginForm input[name="email"]')?.value;
        if (loginEmail) document.getElementById('reset_email').value = loginEmail;
      }
    };

    window.closeResetModal = function () {
      const modal = document.getElementById('resetModal');
      if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.style.display = 'none', 300);
        document.body.style.overflow = '';
      }
    };

    window.handleResetSubmit = function () {
      const emailInput = document.getElementById('reset_email');
      const email = emailInput ? emailInput.value : '';
      const resetBtn = document.querySelector('#reset-initial-view button');

      if (email && email.includes('@')) {
        if (resetBtn) {
          resetBtn.disabled = true;
          resetBtn.innerHTML = '<span class="spinner"></span> Envoi...';
        }

        const formData = new FormData();
        formData.append('email', email);

        fetch('api.php?action=request_reset', {
          method: 'POST',
          body: formData
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              const initialView = document.getElementById('reset-initial-view');
              const successView = document.getElementById('reset-success-view');
              const emailDisplay = document.getElementById('reset-email-display');
              if (initialView && successView) {
                initialView.style.display = 'none';
                successView.style.display = 'block';
                if (emailDisplay) emailDisplay.textContent = email;
              }
            } else {
              alert(data.message || 'Erreur lors de la réinitialisation.');
              if (resetBtn) {
                resetBtn.disabled = false;
                resetBtn.innerHTML = 'Envoyer le lien &rarr;';
              }
            }
          })
          .catch(err => {
            console.error("Erreur Fetch:", err);
            alert("Erreur de connexion au serveur.");
            if (resetBtn) {
              resetBtn.disabled = false;
              resetBtn.innerHTML = 'Envoyer le lien &rarr;';
            }
          });
      } else {
        alert('Veuillez entrer un email valide.');
      }
    };

    const dropdownToggle = document.getElementById('dropdownToggle');
    const userDropdown = document.getElementById('userDropdown');

    if (dropdownToggle && userDropdown) {
      // Toggle dropdown
      dropdownToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isShow = userDropdown.classList.contains('show');
        if (isShow) {
          userDropdown.classList.remove('show');
          userDropdown.style.display = 'none';
        } else {
          userDropdown.classList.add('show');
          userDropdown.style.display = 'block';
        }
      });

      // Close dropdown when clicking outside
      window.addEventListener('click', () => {
        userDropdown.classList.remove('show');
        userDropdown.style.display = 'none';
      });
    }



    // --- Language Selector Logic ---
    const langBtn = document.getElementById('langBtn');
    const langPopover = document.getElementById('langPopover');

    if (langBtn && langPopover) {
      langBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        langBtn.classList.toggle('active');
        langPopover.classList.toggle('show');
      });

      window.addEventListener('click', () => {
        langBtn.classList.remove('active');
        langPopover.classList.remove('show');
      });
    }
    // AJAX fetch session info
    fetch('api.php?action=session')
      .then(response => response.json())
      .then(data => {
        const userNav = document.getElementById('user-nav-auth');
        const guestNav = document.getElementById('guest-nav-auth');

        if (data.loggedIn) {
          const isAdmin = data.role && data.role.toLowerCase().trim() === 'admin';
          const firstName = data.name.split(' ')[0];

          if (!isAdmin) {
            userNav.classList.add('show');
            if (guestNav) guestNav.classList.remove('show');

            document.getElementById('user-name-text').textContent = firstName;

            const userCircle = document.getElementById('user-initial');
            if (userCircle) {
              userCircle.textContent = firstName.charAt(0).toUpperCase();
              if (typeof getAvatarStyle === 'function') {
                const style = getAvatarStyle(data.name);
                userCircle.style.background = style.bg;
                userCircle.style.color = style.text;
              }
            }

            document.getElementById('dropdown-name').textContent = data.name;
            document.getElementById('user-email-text').textContent = data.email;
          } else {
            userNav.classList.remove('show');
            if (guestNav) guestNav.classList.add('show');
          }
        } else {
          userNav.classList.remove('show');
          if (guestNav) guestNav.classList.add('show');
        }
      })
      .catch(err => {
        console.error('Erreur session:', err);
      });
  
