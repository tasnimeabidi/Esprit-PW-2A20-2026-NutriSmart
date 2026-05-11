/**
 * Insère la barre de navigation commune dans #site-nav-root (liens principaux + session).
 * Barre par défaut : vitrine (burger + logo | Accueil + Contact centrés | session membre si connecté).
 * Pour l’ancienne barre complète (Suivi, Profil, Recettes, Admin…), ajouter sur <body> data-nav-bar="full".
 * Sur <body>, optionnel : data-site-nav="home" | "contact" pour la pilule active (barre simple).
 * Émet window CustomEvent "nutrismart:session" avec detail = réponse JSON api.php?action=session
 */
(function () {
  function sidebarMarkup() {
    return (
      '<div class="sidebar-overlay" id="siteSidebarOverlay" aria-hidden="true"></div>' +
      '<aside class="sidebar-menu" id="siteSidebarMenu" role="dialog" aria-modal="true" aria-label="Menu du site">' +
      '<button type="button" class="sidebar-close" id="siteSidebarClose" aria-label="Fermer le menu">&times;</button>' +
      '<div class="sidebar-search">' +
      '<div class="sidebar-search-field">' +
      '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">' +
      '<circle cx="11" cy="11" r="8" /><path d="M21 21l-4.35-4.35" />' +
      '</svg>' +
      '<input type="search" id="siteSidebarSearch" placeholder="Rechercher sur le site" autocomplete="off" />' +
      '</div>' +
      '</div>' +
      '<nav class="sidebar-links">' +
      '<a href="nutrismart-website.html">' +
      '<svg class="sidebar-link-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>' +
      '<polyline points="9 22 9 12 15 12 15 22"/>' +
      '</svg>' +
      ' Accueil</a>' +
      '<a href="nutrismart-website.html#features">Fonctionnalités</a>' +
      '<a href="nutrismart-website.html#comment">Comment ça marche</a>' +
      '<a href="nutrismart-website.html#quiz">Quizz alimentaire</a>' +
      '<a href="profile.html">Profil nutritionnel</a>' +
      '<a href="recette.php">Aliments &amp; recettes</a>' +
      '<a href="repas.html">Mes repas</a>' +
      '<a href="programme-sport.html">Programme sport</a>' +
      '<a href="plan-repas.html">Plan de repas</a>' +
      '<a href="suivi-statistiques.php">Statistique</a>' +
      '<a href="blog.php">Blog</a>' +
      '<a href="budget-user.php">Budget &amp; courses</a>' +
      '<a href="user-achat.php">Boutique aliments</a>' +
      '</nav>' +
      '</aside>'
    );
  }

  function logoSvg(maskId) {
    var mid = maskId || 'biteMask';
    return (
      '<svg width="34" height="34" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible">' +
      '<mask id="' + mid + '">' +
      '<rect x="-20" y="-20" width="140" height="140" fill="white" />' +
      '<circle cx="92" cy="35" r="18" fill="black" />' +
      '<circle cx="84" cy="62" r="14" fill="black" />' +
      '</mask>' +
      '<g mask="url(#' + mid + ')">' +
      '<path d="M 20 80 C 35 45 65 25 90 10 C 90 60 70 90 20 80 Z" fill="#4a7c59" />' +
      '<path d="M 20 80 C 10 30 40 10 90 10 C 65 25 35 45 20 80 Z" fill="#8fbc8f" />' +
      '</g>' +
      '<path d="M 22 78 L 12 92" stroke="#4a7c59" stroke-width="7" stroke-linecap="round" />' +
      '</svg>'
    );
  }

  function authBlockMarkup() {
    return (
      '<div class="nav-auth" id="user-nav-auth" style="display: none;">' +
      '<div class="user-dropdown">' +
      '<div class="user-badge" id="dropdownToggle">' +
      '<div class="user-avatar" id="user-initial">?</div>' +
      '<span id="user-name-text">Chargement...</span>' +
      '<svg style="width:14px; height:14px; margin-left:4px; opacity:0.6;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="m6 9 6 6 6-6" />' +
      '</svg>' +
      '</div>' +
      '<div class="dropdown-content" id="userDropdown">' +
      '<div class="dropdown-header">' +
      '<span class="user-name" id="dropdown-name">Chargement...</span>' +
      '<span class="user-email" id="user-email-text">email@exemple.com</span>' +
      '</div>' +
      '<div class="dropdown-actions">' +
      '<a href="profile.html" class="dropdown-item">' +
      '<svg style="width:18px; height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />' +
      '<circle cx="12" cy="7" r="4" />' +
      '</svg>' +
      ' Mon Profil' +
      '</a>' +
      '<a href="api.php?action=logout" class="dropdown-item logout">' +
      '<svg style="width:18px; height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />' +
      '<polyline points="16 17 21 12 16 7" />' +
      '<line x1="21" y1="12" x2="9" y2="12" />' +
      '</svg>' +
      ' Déconnexion' +
      '</a>' +
      '</div>' +
      '</div>' +
      '</div>' +
      '</div>' +
      '<div id="guest-nav-auth" class="nav-guest" style="display:none" aria-hidden="true"></div>'
    );
  }

  /** Session vitrine : avatar initiales + nom complet + « Membre » + chevron (maquette) */
  function authBlockMarkupVitrine() {
    return (
      '<div class="nav-auth nav-auth--vitrine" id="user-nav-auth" style="display: none;">' +
      '<div class="user-dropdown">' +
      '<div class="user-badge user-badge--vitrine" id="dropdownToggle">' +
      '<div class="user-avatar user-avatar--vitrine" id="user-initial">?</div>' +
      '<div class="user-badge-vitrine-meta">' +
      '<span class="user-badge-vitrine-name" id="user-nav-display-name">…</span>' +
      '<span class="user-badge-vitrine-role">Membre</span>' +
      '</div>' +
      '<svg class="user-badge-vitrine-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<path d="m6 9 6 6 6-6" />' +
      '</svg>' +
      '</div>' +
      '<div class="dropdown-content" id="userDropdown">' +
      '<div class="dropdown-header">' +
      '<span class="user-name" id="dropdown-name">Chargement...</span>' +
      '<span class="user-email" id="user-email-text">email@exemple.com</span>' +
      '</div>' +
      '<div class="dropdown-actions">' +
      '<a href="profile.html" class="dropdown-item">' +
      '<svg style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />' +
      '<circle cx="12" cy="7" r="4" />' +
      '</svg>' +
      ' Mon Profil' +
      '</a>' +
      '<a href="api.php?action=logout" class="dropdown-item logout">' +
      '<svg style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />' +
      '<polyline points="16 17 21 12 16 7" />' +
      '<line x1="21" y1="12" x2="9" y2="12" />' +
      '</svg>' +
      ' Déconnexion' +
      '</a>' +
      '</div>' +
      '</div>' +
      '</div>' +
      '</div>' +
      '<div id="guest-nav-auth" class="nav-guest" style="display:none" aria-hidden="true"></div>'
    );
  }

  function initialsFromName(name) {
    var parts = (name || '').trim().split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
      return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }
    if (parts.length === 1) {
      return parts[0].substring(0, Math.min(2, parts[0].length)).toUpperCase();
    }
    return '?';
  }

  /** Barre vitrine : gauche burger + logo | centre Accueil + Contact | droite session si connecté */
  function navMarkupSimple() {
    return (
      '<nav id="navbar" class="site-nav-bar site-nav-bar--simple">' +
      '<div class="nav-simple-left">' +
      '<button type="button" class="nav-hamburger" id="siteNavHamburger" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="siteSidebarMenu">' +
      '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">' +
      '<path d="M3 6h18M3 12h18M3 18h18" />' +
      '</svg>' +
      '</button>' +
      '<a href="nutrismart-website.html" class="nav-logo">' +
      logoSvg('biteMask') +
      '<span class="nav-logo-title">NutriSmart</span>' +
      '</a>' +
      '</div>' +
      '<ul class="nav-links nav-links--center nav-links--simple">' +
      '<li><a href="nutrismart-website.html" id="site-nav-accueil" class="nav-simple-link" data-i18n="nav_home">Accueil</a></li>' +
      '<li><a href="contact.html" id="site-nav-contact" class="nav-simple-link" data-i18n="nav_contact">Contact</a></li>' +
      '</ul>' +
      '<div class="nav-trailing nav-trailing--simple">' +
      authBlockMarkupVitrine() +
      '<div class="nav-simple-tools">' +
      '<a href="contact.html" class="nav-simple-bell" aria-label="Contact et aide">' +
      '<span class="nav-simple-bell-icon" aria-hidden="true">🔔</span>' +
      '</a>' +
      '<a href="login.html" class="nav-simple-auth-pill nav-simple-auth-pill--login" id="nav-simple-login-pill">Connexion</a>' +
      '<a href="api.php?action=logout" class="nav-simple-auth-pill nav-simple-auth-pill--logout" id="nav-simple-logout-pill" style="display:none">Déconnexion</a>' +
      '</div>' +
      '</div>' +
      '</nav>'
    );
  }

  function navMarkup() {
    return (
      '<nav id="navbar" class="site-nav-bar">' +
      '<div class="nav-start-cluster">' +
      '<div class="nav-start-tools">' +
      '<button type="button" class="nav-hamburger" id="siteNavHamburger" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="siteSidebarMenu">' +
      '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">' +
      '<path d="M3 6h18M3 12h18M3 18h18" />' +
      '</svg>' +
      '</button>' +
      '<a href="register.html" id="nav-register-top" class="nav-register-cta nav-register-cta--sidebar">' +
      '<svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>' +
      '<circle cx="12" cy="7" r="4"></circle>' +
      '</svg>' +
      '<span data-i18n="nav_register">S\'inscrire</span>' +
      '</a>' +
      '</div>' +
      '<div class="nav-brand-stack">' +
      '<a href="accueil.html" class="nav-logo">' +
      logoSvg('biteMask') +
      '<span class="nav-logo-title">NutriSmart</span>' +
      '</a>' +
      '<a href="proposer-recette.php" class="nav-propose-recette">PROPOSER UNE RECETTE</a>' +
      '</div>' +
      '</div>' +
      '<ul class="nav-links nav-links--center">' +
      '<li><a href="accueil.html" id="site-nav-accueil" data-i18n="nav_home">Accueil</a></li>' +
      '<li><a href="suivi-statistiques.php" id="site-nav-suivi">Suivi</a></li>' +
      '<li><a href="profile.html" id="site-nav-profil">Profil</a></li>' +
      '<li><a href="recette.php" id="site-nav-recettes">Recettes</a></li>' +
      '<li><a href="contact.html" id="site-nav-contact" data-i18n="nav_contact">Contact</a></li>' +
      '<li><a href="../backoffice/nutrismart-dashboard.html" id="site-nav-admin" class="nav-link--admin">Admin</a></li>' +
      '</ul>' +
      '<div class="nav-trailing">' +
      authBlockMarkup() +
      '</div>' +
      '</nav>'
    );
  }

  function applyActiveNav() {
    var mode = (document.body && document.body.getAttribute('data-site-nav')) || '';
    var fullBar = document.body && document.body.getAttribute('data-nav-bar') === 'full';
    var ids = [
      'site-nav-accueil',
      'site-nav-suivi',
      'site-nav-profil',
      'site-nav-recettes',
      'site-nav-contact',
      'site-nav-admin',
      'site-nav-budget',
      'site-nav-boutique',
      'site-nav-profile'
    ];
    var map = {
      home: 'site-nav-accueil',
      contact: 'site-nav-contact',
      suivi: 'site-nav-suivi',
      profil: fullBar ? 'site-nav-profil' : 'site-nav-profile',
      profile: fullBar ? 'site-nav-profil' : 'site-nav-profile',
      recettes: 'site-nav-recettes',
      admin: 'site-nav-admin',
      budget: 'site-nav-budget',
      boutique: 'site-nav-boutique'
    };
    var i;
    for (i = 0; i < ids.length; i++) {
      var el = document.getElementById(ids[i]);
      if (el) el.classList.remove('active');
    }
    var activeId = map[mode];
    if (!activeId && !fullBar) {
      var p = (window.location.pathname || '').replace(/\\/g, '/').toLowerCase();
      var file = p.split('/').pop() || '';
      if (p.indexOf('contact') !== -1) {
        activeId = 'site-nav-contact';
      } else if (
        file === 'nutrismart-website.html' ||
        file === 'nutrismart-home.html' ||
        file === 'accueil.html'
      ) {
        activeId = 'site-nav-accueil';
      }
    }
    if (activeId) {
      var a = document.getElementById(activeId);
      if (a) a.classList.add('active');
    }
  }

  window.closeSidebar = function () {
    var overlay = document.getElementById('siteSidebarOverlay');
    var panel = document.getElementById('siteSidebarMenu');
    var btn = document.getElementById('siteNavHamburger');
    if (overlay) overlay.classList.remove('active');
    if (panel) panel.classList.remove('active');
    document.body.style.overflow = '';
    if (btn) btn.setAttribute('aria-expanded', 'false');
  };

  window.openSidebar = function () {
    var overlay = document.getElementById('siteSidebarOverlay');
    var panel = document.getElementById('siteSidebarMenu');
    var btn = document.getElementById('siteNavHamburger');
    if (overlay) overlay.classList.add('active');
    if (panel) panel.classList.add('active');
    document.body.style.overflow = 'hidden';
    if (btn) btn.setAttribute('aria-expanded', 'true');
  };

  window.performSiteSearch = function () {};

  function bindSidebarNav() {
    var btn = document.getElementById('siteNavHamburger');
    var overlay = document.getElementById('siteSidebarOverlay');
    var closeBtn = document.getElementById('siteSidebarClose');
    var panel = document.getElementById('siteSidebarMenu');
    if (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        window.openSidebar();
      });
    }
    if (overlay) {
      overlay.addEventListener('click', function () {
        window.closeSidebar();
      });
    }
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        window.closeSidebar();
      });
    }
    if (panel) {
      var links = panel.querySelectorAll('a');
      var i;
      for (i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function () {
          window.closeSidebar();
        });
      }
    }
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') window.closeSidebar();
    });
  }

  function bindDropdown() {
    var dropdownToggle = document.getElementById('dropdownToggle');
    var userDropdown = document.getElementById('userDropdown');
    if (dropdownToggle && userDropdown) {
      dropdownToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('show');
      });
      window.addEventListener('click', function () {
        if (userDropdown.classList.contains('show')) userDropdown.classList.remove('show');
      });
    }
  }

  function fireSessionEvent(data) {
    try {
      window.__nutrismartSession = data;
      window.dispatchEvent(new CustomEvent('nutrismart:session', { detail: data }));
    } catch (e) {
    }
  }

  function notifySessionListeners(data) {
    function go() {
      fireSessionEvent(data);
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', go);
    } else {
      setTimeout(go, 0);
    }
  }

  function loadSession() {
    fetch('api.php?action=session')
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        var userNav = document.getElementById('user-nav-auth');
        var guestNav = document.getElementById('guest-nav-auth');
        var regTop = document.getElementById('nav-register-top');

        var loginPill = document.getElementById('nav-simple-login-pill');
        var logoutPill = document.getElementById('nav-simple-logout-pill');

        if (userNav) {
          if (data.loggedIn) {
            var qb = document.getElementById('quizBanner');
            if (qb) qb.style.display = 'none';
            var isAdmin = data.role && data.role.toLowerCase().trim() === 'admin';
            var firstName = (data.name || '').split(' ')[0];
            if (loginPill) loginPill.style.display = 'none';
            if (logoutPill) logoutPill.style.display = '';

            if (!isAdmin) {
              userNav.classList.add('show');
              if (guestNav) guestNav.classList.remove('show');
              if (regTop) regTop.style.display = 'none';

              var nt = document.getElementById('user-name-text');
              if (nt) nt.textContent = firstName;

              var vitrineNameEl = document.getElementById('user-nav-display-name');
              if (vitrineNameEl) vitrineNameEl.textContent = data.name || firstName || '';

              var userCircle = document.getElementById('user-initial');
              if (userCircle) {
                userCircle.textContent = vitrineNameEl ? initialsFromName(data.name) : (firstName ? firstName.charAt(0).toUpperCase() : '?');
                if (typeof getAvatarStyle === 'function') {
                  var style = getAvatarStyle(data.name);
                  userCircle.style.background = style.bg;
                  userCircle.style.color = style.text;
                }
              }
              var dn = document.getElementById('dropdown-name');
              if (dn) dn.textContent = data.name;
              var em = document.getElementById('user-email-text');
              if (em) em.textContent = data.email;
            } else {
              userNav.classList.remove('show');
              if (guestNav) guestNav.classList.add('show');
              if (regTop) regTop.style.display = '';
            }
          } else {
            if (loginPill) loginPill.style.display = '';
            if (logoutPill) logoutPill.style.display = 'none';
            userNav.classList.remove('show');
            if (guestNav) guestNav.classList.add('show');
            if (regTop) regTop.style.display = '';
          }
        }
        notifySessionListeners(data);
      })
      .catch(function (err) {
        console.error('Erreur session:', err);
        notifySessionListeners({ loggedIn: false, sessionError: true });
      });
  }

  function mount() {
    var root = document.getElementById('site-nav-root');
    if (!root || root.getAttribute('data-mounted') === '1') return;
    var fullBar =
      document.body && document.body.getAttribute('data-nav-bar') === 'full';
    root.innerHTML = (fullBar ? navMarkup() : navMarkupSimple()) + sidebarMarkup();
    root.setAttribute('data-mounted', '1');
    applyActiveNav();
    bindDropdown();
    bindSidebarNav();
    loadSession();
  }

  mount();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
  }
})();
