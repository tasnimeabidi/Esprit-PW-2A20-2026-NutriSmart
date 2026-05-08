<?php
include_once '../../controllers/SuiviController.php';
$controller = new SuiviController();

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'date DESC';
$logs = $controller->listLogs(1, $search, $sort);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NutriSmart — Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:      #0d1210;
    --surface: #141a16;
    --card:    #1a2420;
    --border:  #253028;
    --primary: #3dba52;
    --lime:    #8bc34a;
    --gold:    #f9a825;
    --red:     #e53935;
    --blue:    #29b6f6;
    --purple:  #ab47bc;
    --white:   #e8f0e9;
    --muted:   #5a7060;
    --mono:    'Space Mono', monospace;
    --sans:    'Syne', sans-serif;
  }

  html, body { height: 100%; }

  body {
    font-family: var(--sans);
    background: var(--bg);
    color: var(--white);
    display: grid;
    grid-template-columns: 240px 1fr;
    grid-template-rows: 56px 1fr;
    min-height: 100vh;
  }

  /* ── TOPBAR ── */
  .topbar {
    grid-column: 1 / -1;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 1.5rem 0 1.5rem;
    position: sticky; top: 0; z-index: 50;
  }

  .topbar-logo {
    display: flex; align-items: center; gap: .6rem;
    font-family: var(--sans); font-size: 1.1rem; font-weight: 800;
    color: var(--white); text-decoration: none; letter-spacing: -.01em;
  }
  .logo-dot {
    width: 28px; height: 28px; border-radius: 6px;
    background: var(--primary); display: grid; place-items: center;
    font-size: .85rem;
  }
  .logo-badge {
    font-size: .65rem; font-weight: 700; background: rgba(61,186,82,.15);
    color: var(--primary); padding: .1rem .4rem; border-radius: .25rem;
    letter-spacing: .05em;
  }

  .topbar-right {
    display: flex; align-items: center; gap: 1.2rem;
  }
  .notif-btn {
    width: 34px; height: 34px; border-radius: 8px;
    background: var(--card); border: 1px solid var(--border);
    display: grid; place-items: center; cursor: pointer;
    font-size: .9rem; transition: border-color .2s; position: relative;
  }
  .notif-btn:hover { border-color: var(--primary); }
  .notif-dot {
    position: absolute; top: 6px; right: 6px; width: 6px; height: 6px;
    background: var(--gold); border-radius: 50%;
  }

  .admin-avatar {
    display: flex; align-items: center; gap: .6rem; cursor: pointer;
  }
  .avatar-img {
    width: 32px; height: 32px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--lime));
    display: grid; place-items: center; font-size: .85rem; font-weight: 700;
    color: var(--bg);
  }
  .admin-info { line-height: 1.2; }
  .admin-name { font-size: .82rem; font-weight: 700; }
  .admin-role { font-size: .68rem; color: var(--muted); }

  /* ── SIDEBAR ── */
  aside {
    background: var(--surface);
    border-right: 1px solid var(--border);
    padding: 1.2rem .75rem;
    display: flex; flex-direction: column; gap: .2rem;
    overflow-y: auto;
  }

  .nav-section-label {
    font-size: .62rem; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: var(--muted);
    padding: .8rem .6rem .3rem; margin-top: .4rem;
  }

  .nav-item {
    display: flex; align-items: center; gap: .7rem;
    padding: .55rem .8rem; border-radius: .5rem;
    font-size: .83rem; font-weight: 600; color: var(--muted);
    cursor: pointer; text-decoration: none; transition: all .15s;
    border: 1px solid transparent;
  }
  .nav-item:hover { background: var(--card); color: var(--white); }
  .nav-item.active {
    background: rgba(61,186,82,.12); color: var(--primary);
    border-color: rgba(61,186,82,.2);
  }
  .nav-icon { width: 18px; text-align: center; font-size: .95rem; }
  .nav-badge {
    margin-left: auto; font-size: .62rem; font-weight: 700;
    background: var(--primary); color: var(--bg);
    padding: .1rem .4rem; border-radius: .25rem; font-family: var(--mono);
  }
  .nav-badge.warn { background: var(--gold); color: var(--bg); }

  /* ── MAIN ── */
  main {
    overflow-y: auto; padding: 1.8rem 2rem;
    display: flex; flex-direction: column; gap: 1.5rem;
  }

  /* ── PAGE HEADER ── */
  .page-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
  }
  .page-title { font-size: 1.5rem; font-weight: 800; color: var(--white); }
  .page-sub { font-size: .82rem; color: var(--muted); margin-top: .2rem; font-family: var(--mono); }

  .header-actions { display: flex; gap: .7rem; }
  .btn-sm {
    padding: .45rem 1.1rem; border-radius: .5rem; font-size: .8rem;
    font-weight: 700; cursor: pointer; border: none; transition: all .2s;
    font-family: var(--sans);
  }
  .btn-ghost {
    background: var(--card); color: var(--white); border: 1px solid var(--border);
  }
  .btn-ghost:hover { border-color: var(--primary); color: var(--primary); }
  .btn-green { background: var(--primary); color: var(--bg); }
  .btn-green:hover { opacity: .88; }

  /* ── KPI GRID ── */
  .kpi-grid {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;
  }

  .kpi-card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: .9rem; padding: 1.2rem 1.3rem;
    display: flex; flex-direction: column; gap: .7rem;
    transition: border-color .2s;
  }
  .kpi-card:hover { border-color: var(--primary); }

  .kpi-top { display: flex; align-items: center; justify-content: space-between; }
  .kpi-label { font-size: .72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; }
  .kpi-icon {
    width: 32px; height: 32px; border-radius: .5rem;
    display: grid; place-items: center; font-size: .9rem;
  }

  .kpi-val {
    font-family: var(--mono); font-size: 1.9rem; font-weight: 700; color: var(--white);
    line-height: 1;
  }
  .kpi-trend {
    display: flex; align-items: center; gap: .3rem;
    font-size: .72rem; font-weight: 700;
  }
  .kpi-trend.up { color: var(--primary); }
  .kpi-trend.down { color: var(--red); }

  /* ── MAIN GRID ── */
  .main-grid {
    display: grid; grid-template-columns: 2fr 1fr; gap: 1.2rem;
  }

  /* ── CHART CARD ── */
  .chart-card {
    background: var(--card); border: 1px solid var(--border); border-radius: .9rem;
    padding: 1.3rem;
  }
  .card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.2rem;
  }
  .card-title { font-size: .9rem; font-weight: 700; }
  .card-sub { font-size: .72rem; color: var(--muted); margin-top: .1rem; font-family: var(--mono); }

  .chart-tabs { display: flex; gap: .3rem; }
  .chart-tab {
    font-size: .7rem; font-weight: 700; padding: .25rem .65rem;
    border-radius: .3rem; cursor: pointer; background: var(--border); color: var(--muted);
    border: none; font-family: var(--sans);
  }
  .chart-tab.active { background: rgba(61,186,82,.15); color: var(--primary); }

  /* SVG bar chart */
  .bar-chart { width: 100%; height: 160px; overflow: visible; }

  /* ── RECENT USERS ── */
  .users-table { width: 100%; border-collapse: collapse; }
  .users-table th {
    font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
    color: var(--muted); text-align: left; padding: .5rem .6rem;
    border-bottom: 1px solid var(--border);
  }
  .users-table td {
    padding: .7rem .6rem; font-size: .82rem;
    border-bottom: 1px solid rgba(37,48,40,.6);
  }
  .users-table tr:last-child td { border-bottom: none; }
  .users-table tr:hover td { background: rgba(61,186,82,.03); }

  .user-cell { display: flex; align-items: center; gap: .6rem; }
  .u-avatar {
    width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center;
    font-size: .7rem; font-weight: 700; color: var(--bg); flex-shrink: 0;
  }
  .u-name { font-weight: 600; font-size: .82rem; }
  .u-email { font-size: .7rem; color: var(--muted); font-family: var(--mono); }

  .status-pill {
    font-size: .65rem; font-weight: 700; padding: .2rem .6rem; border-radius: 2rem;
    letter-spacing: .04em;
  }
  .s-active { background: rgba(61,186,82,.15); color: var(--primary); }
  .s-inactive { background: rgba(229,57,53,.12); color: #ef5350; }
  .s-pending { background: rgba(249,168,37,.12); color: var(--gold); }

  /* ── SIDE PANELS ── */
  .side-panels { display: flex; flex-direction: column; gap: 1.2rem; }

  .mini-card {
    background: var(--card); border: 1px solid var(--border); border-radius: .9rem;
    padding: 1.2rem;
  }

  /* Modules usage donut-style */
  .module-usage { display: flex; flex-direction: column; gap: .65rem; margin-top: .8rem; }
  .module-info { display: flex; justify-content: space-between; font-size: .78rem; margin-bottom: .3rem; }
  .module-name { font-weight: 600; }
  .module-pct { font-family: var(--mono); color: var(--muted); }
  .prog-bar { height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; }
  .prog-fill { height: 100%; border-radius: 3px; transition: width .6s ease; }

  /* Activity feed */
  .activity-list { display: flex; flex-direction: column; gap: 0; margin-top: .6rem; }
  .activity-item {
    display: flex; gap: .8rem; padding: .7rem 0;
    border-bottom: 1px solid rgba(37,48,40,.6);
  }
  .activity-item:last-child { border-bottom: none; }
  .act-dot {
    width: 8px; height: 8px; border-radius: 50%;
    flex-shrink: 0; margin-top: .35rem;
  }
  .act-text { font-size: .78rem; line-height: 1.5; }
  .act-time { font-size: .68rem; color: var(--muted); font-family: var(--mono); }

  /* ── BOTTOM GRID ── */
  .bottom-grid {
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.2rem;
  }

  /* Top foods table */
  .food-row {
    display: flex; align-items: center; gap: .8rem;
    padding: .6rem 0; border-bottom: 1px solid rgba(37,48,40,.6);
    font-size: .8rem;
  }
  .food-row:last-child { border-bottom: none; }
  .food-rank {
    width: 22px; height: 22px; border-radius: .3rem;
    background: var(--border); display: grid; place-items: center;
    font-family: var(--mono); font-size: .7rem; font-weight: 700; flex-shrink: 0;
  }
  .food-name { flex: 1; font-weight: 600; }
  .food-count { font-family: var(--mono); font-size: .72rem; color: var(--muted); }

  /* Grocery stats */
  .budget-display {
    text-align: center; padding: 1rem 0;
  }
  .budget-big {
    font-family: var(--mono); font-size: 2.4rem; font-weight: 700; color: var(--gold);
  }
  .budget-sub { font-size: .72rem; color: var(--muted); margin-top: .2rem; }
  .budget-progress {
    margin-top: 1rem; height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;
  }
  .budget-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, var(--gold), #f57f17); width: 68%; }

  /* Quick actions */
  .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-top: .8rem; }
  .qa-btn {
    background: var(--border); border: 1px solid transparent;
    border-radius: .6rem; padding: .8rem; text-align: center;
    cursor: pointer; transition: all .2s; font-family: var(--sans);
    color: var(--white);
  }
  .qa-btn:hover { border-color: var(--primary); background: rgba(61,186,82,.08); }
  .qa-icon { font-size: 1.2rem; margin-bottom: .3rem; }
  .qa-label { font-size: .72rem; font-weight: 600; color: var(--muted); }

  /* ── SCROLLBAR ── */
  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

  /* ── MODAL ── */
  .modal {
    display: none; position: fixed; z-index: 1000; left: 0; top: 0;
    width: 100%; height: 100%; background: rgba(0,0,0,0.8);
    backdrop-filter: blur(4px); place-items: center;
  }
  .modal-content {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 1.2rem; padding: 2rem; width: 450px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    animation: fadeIn .3s ease both;
  }
  .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
  .modal-title { font-size: 1.2rem; font-weight: 800; color: var(--primary); }
  .close-modal { cursor: pointer; font-size: 1.5rem; color: var(--muted); }
  .close-modal:hover { color: var(--white); }

  .admin-form { display: flex; flex-direction: column; gap: 1rem; }
  .form-group { display: flex; flex-direction: column; gap: .4rem; }
  .form-group label { font-size: .75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; }
  .admin-input {
    background: var(--card); border: 1px solid var(--border);
    border-radius: .5rem; padding: .75rem; color: var(--white);
    font-family: var(--sans); font-size: .9rem; outline: none;
  }
  .admin-input:focus { border-color: var(--primary); }

  @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }
  .kpi-card { animation: fadeIn .4s ease both; }
  .kpi-card:nth-child(1){animation-delay:.05s}
  .kpi-card:nth-child(2){animation-delay:.1s}
  .kpi-card:nth-child(3){animation-delay:.15s}
  .kpi-card:nth-child(4){animation-delay:.2s}

  /* ── RESPONSIVE ── */
  @media (max-width: 1100px) {
    body { grid-template-columns: 200px 1fr; }
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .bottom-grid { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 800px) {
    body { grid-template-columns: 1fr; }
    aside { display: none; }
    .main-grid { grid-template-columns: 1fr; }
    .bottom-grid { grid-template-columns: 1fr; }
    main { padding: 1.2rem; }
  }
</style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <a href="nutrismart-website.html" class="topbar-logo">
    <div class="logo-dot">🍎</div>
    NutriSmart
    <span class="logo-badge">ADMIN</span>
  </a>

  <div class="topbar-right">
    <div class="notif-btn" title="Notifications">
      🔔
      <div class="notif-dot"></div>
    </div>
    <div class="notif-btn" title="Paramètres">⚙️</div>
    <div class="admin-avatar">
      <div class="avatar-img">A</div>
      <div class="admin-info">
        <div class="admin-name">Admin Principal</div>
        <div class="admin-role">Super Administrator</div>
      </div>
    </div>
  </div>
</header>

<script>
  function showView(viewId, element) {
    // Hide all views
    document.querySelectorAll('.content-view').forEach(v => v.style.display = 'none');
    // Show selected view
    document.getElementById(viewId).style.display = 'flex';
    
    // Update active state in sidebar
    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
    if(element) element.classList.add('active');
    else {
        // Find element by viewId if not provided (for reload)
        const items = document.querySelectorAll('.nav-item');
        items.forEach(item => {
            if(item.getAttribute('onclick') && item.getAttribute('onclick').includes(viewId)) {
                item.classList.add('active');
            }
        });
    }

    // Save state
    localStorage.setItem('activeView', viewId);
  }

  // Restore state on reload
  window.addEventListener('DOMContentLoaded', () => {
    const savedView = localStorage.getItem('activeView') || 'view-welcome';
    showView(savedView, null);
  });
</script>

<!-- SIDEBAR -->
<aside>
  <div style="padding: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 1rem;">
    <a href="../frontoffice/nutrismart-website.html" style="color: var(--lime); text-decoration: none; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 0.6rem;">
      <span style="font-size: 1.1rem;">←</span> Retour au site
    </a>
  </div>
  <div class="nav-section-label">Principal</div>
  <a class="nav-item active" href="#" onclick="showView('view-welcome', this)">
    <span class="nav-icon">🏠</span> Accueil Admin
  </a>
  <a class="nav-item" href="#" onclick="showView('view-dashboard', this)">
    <span class="nav-icon">📊</span> Tableau de bord
  </a>

  <div class="nav-section-label">Modules NutriSmart</div>
  <a class="nav-item" href="#">
    <span class="nav-icon">👥</span> Utilisateurs
  </a>
  <a class="nav-item" href="#">
    <span class="nav-icon">🥗</span> Aliments
  </a>
  <a class="nav-item" href="#">
    <span class="nav-icon">📅</span> Plans de repas
  </a>
  <a class="nav-item" href="#" onclick="showView('view-suivi', this)">
    <span class="nav-icon">📈</span> Suivi et Statistiques
  </a>
  <a class="nav-item" href="#">
    <span class="nav-icon">🛒</span> Courses & Budget
  </a>
  <a class="nav-item" href="#">
    <span class="nav-icon">📖</span> Recettes
  </a>

  <div class="nav-section-label">Système & Logs</div>
  <a class="nav-item" href="#">
    <span class="nav-icon">📤</span> Exports
  </a>
  <a class="nav-item" href="#">
    <span class="nav-icon">🔒</span> Permissions
  </a>
  <a class="nav-item" href="#">
    <span class="nav-icon">📋</span> Logs d'activité
  </a>
  <a class="nav-item" href="#">
    <span class="nav-icon">⚙️</span> Paramètres
  </a>

  <div style="margin-top:auto;padding-top:1.5rem">
    <a class="nav-item" href="../frontoffice/nutrismart-website.html" style="color:var(--primary)">
      <span class="nav-icon">🌐</span> Voir le site
    </a>
  </div>
</aside>

<!-- MAIN CONTENT -->
<main>
  <style>
    .content-view { display: none; flex-direction: column; gap: 1.5rem; width: 100%; }
    #view-welcome { display: flex; justify-content: center; align-items: center; text-align: center; height: 100%; color: var(--muted); }
    .empty-state { max-width: 400px; }
  </style>

  <!-- Welcome View -->
  <div id="view-welcome" class="content-view">
    <div class="empty-state">
      <div style="font-size: 3rem; margin-bottom: 1rem;">🏢</div>
      <h2 style="color: var(--white); margin-bottom: 0.5rem;">Administration NutriSmart</h2>
      <p>Sélectionnez un module dans le menu de gauche pour visualiser et gérer les données.</p>
    </div>
  </div>

  <!-- Dashboard View (Original main content) -->
  <div id="view-dashboard" class="content-view">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <div class="page-title">Tableau de bord</div>
      <div class="page-sub">Samedi 28 Mars 2026 · Dernière sync : il y a 2 min</div>
    </div>
    <div class="header-actions">
      <button class="btn-sm btn-ghost">📤 Exporter rapport</button>
      <button class="btn-sm btn-green">+ Ajouter aliment</button>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-label">Utilisateurs actifs</div>
        <div class="kpi-icon" style="background:rgba(61,186,82,.12)">👤</div>
      </div>
      <div class="kpi-val">284</div>
      <div class="kpi-trend up">↑ +18% ce mois</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-label">Aliments indexés</div>
        <div class="kpi-icon" style="background:rgba(41,182,246,.12)">🥗</div>
      </div>
      <div class="kpi-val">1,247</div>
      <div class="kpi-trend up">↑ +43 cette semaine</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-label">Plans IA générés</div>
        <div class="kpi-icon" style="background:rgba(249,168,37,.12)">🤖</div>
      </div>
      <div class="kpi-val">892</div>
      <div class="kpi-trend up">↑ +32% vs mois dernier</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-label">Recettes en attente</div>
        <div class="kpi-icon" style="background:rgba(229,57,53,.12)">📖</div>
      </div>
      <div class="kpi-val">17</div>
      <div class="kpi-trend down">⚠ Modération requise</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-label">Suivi global</div>
        <div class="kpi-icon" style="background:rgba(171,71,188,.12)">📉</div>
      </div>
      <div class="kpi-val">94%</div>
      <div class="kpi-trend up">↑ Engagement optimal</div>
    </div>
  </div>

  <!-- Main Grid: Chart + Activity -->
  <div class="main-grid">
    <!-- Left: Charts + Users -->
    <div style="display:flex;flex-direction:column;gap:1.2rem">

      <!-- Bar chart - Registrations -->
      <div class="chart-card">
        <div class="card-header">
          <div>
            <div class="card-title">Inscriptions utilisateurs</div>
            <div class="card-sub">Nouveaux comptes par semaine</div>
          </div>
          <div class="chart-tabs">
            <button class="chart-tab">7J</button>
            <button class="chart-tab active">30J</button>
            <button class="chart-tab">3M</button>
          </div>
        </div>
        <!-- Inline SVG bar chart -->
        <svg class="bar-chart" viewBox="0 0 560 160" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="barGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#3dba52"/>
              <stop offset="100%" stop-color="#3dba52" stop-opacity="0.4"/>
            </linearGradient>
          </defs>
          <!-- Grid lines -->
          <line x1="0" y1="130" x2="560" y2="130" stroke="#253028" stroke-width="1"/>
          <line x1="0" y1="100" x2="560" y2="100" stroke="#253028" stroke-width="1" stroke-dasharray="4"/>
          <line x1="0" y1="70" x2="560" y2="70" stroke="#253028" stroke-width="1" stroke-dasharray="4"/>
          <line x1="0" y1="40" x2="560" y2="40" stroke="#253028" stroke-width="1" stroke-dasharray="4"/>
          <!-- Labels -->
          <text x="0" y="148" fill="#5a7060" font-size="10" font-family="Space Mono">Sem 1</text>
          <text x="78" y="148" fill="#5a7060" font-size="10" font-family="Space Mono">Sem 2</text>
          <text x="158" y="148" fill="#5a7060" font-size="10" font-family="Space Mono">Sem 3</text>
          <text x="238" y="148" fill="#5a7060" font-size="10" font-family="Space Mono">Sem 4</text>
          <text x="318" y="148" fill="#5a7060" font-size="10" font-family="Space Mono">Sem 5</text>
          <text x="398" y="148" fill="#5a7060" font-size="10" font-family="Space Mono">Sem 6</text>
          <text x="478" y="148" fill="#5a7060" font-size="10" font-family="Space Mono">Sem 7</text>
          <!-- Bars -->
          <rect x="8"   y="90" width="50" height="40" rx="4" fill="url(#barGrad)"/>
          <rect x="88"  y="75" width="50" height="55" rx="4" fill="url(#barGrad)"/>
          <rect x="168" y="60" width="50" height="70" rx="4" fill="url(#barGrad)"/>
          <rect x="248" y="80" width="50" height="50" rx="4" fill="url(#barGrad)"/>
          <rect x="328" y="45" width="50" height="85" rx="4" fill="url(#barGrad)"/>
          <rect x="408" y="50" width="50" height="80" rx="4" fill="url(#barGrad)"/>
          <rect x="488" y="30" width="50" height="100" rx="4" fill="#3dba52"/>
          <!-- Values -->
          <text x="33"  y="85" fill="#e8f0e9" font-size="11" text-anchor="middle" font-family="Space Mono">12</text>
          <text x="113" y="70" fill="#e8f0e9" font-size="11" text-anchor="middle" font-family="Space Mono">18</text>
          <text x="193" y="55" fill="#e8f0e9" font-size="11" text-anchor="middle" font-family="Space Mono">24</text>
          <text x="273" y="75" fill="#e8f0e9" font-size="11" text-anchor="middle" font-family="Space Mono">19</text>
          <text x="353" y="40" fill="#e8f0e9" font-size="11" text-anchor="middle" font-family="Space Mono">31</text>
          <text x="433" y="45" fill="#e8f0e9" font-size="11" text-anchor="middle" font-family="Space Mono">28</text>
          <text x="513" y="25" fill="#3dba52" font-size="11" text-anchor="middle" font-family="Space Mono" font-weight="700">38</text>
        </svg>
      </div>

      <!-- Users Table -->
      <div class="chart-card">
        <div class="card-header">
          <div>
            <div class="card-title">Derniers utilisateurs inscrits</div>
            <div class="card-sub">5 comptes les plus récents</div>
          </div>
          <button class="btn-sm btn-ghost">Voir tous →</button>
        </div>
        <table class="users-table">
          <thead>
            <tr>
              <th>Utilisateur</th>
              <th>Module principal</th>
              <th>Plans IA</th>
              <th>Statut</th>
              <th>Inscrit le</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="u-avatar" style="background:#3F51B5">S</div>
                  <div>
                    <div class="u-name">Sarah Benali</div>
                    <div class="u-email">sarah.b@mail.com</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--muted);font-size:.78rem">Meal Planning</td>
              <td style="font-family:var(--mono);font-size:.8rem;color:var(--primary)">14</td>
              <td><span class="status-pill s-active">ACTIF</span></td>
              <td style="font-family:var(--mono);font-size:.72rem;color:var(--muted)">28/03/26</td>
            </tr>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="u-avatar" style="background:#F57C00">K</div>
                  <div>
                    <div class="u-name">Karim Mansouri</div>
                    <div class="u-email">k.mansouri@mail.com</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--muted);font-size:.78rem">Progress Tracking</td>
              <td style="font-family:var(--mono);font-size:.8rem;color:var(--primary)">7</td>
              <td><span class="status-pill s-active">ACTIF</span></td>
              <td style="font-family:var(--mono);font-size:.72rem;color:var(--muted)">27/03/26</td>
            </tr>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="u-avatar" style="background:#00796B">L</div>
                  <div>
                    <div class="u-name">Lina Trabelsi</div>
                    <div class="u-email">lina.t@mail.com</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--muted);font-size:.78rem">Recipes</td>
              <td style="font-family:var(--mono);font-size:.8rem;color:var(--muted)">2</td>
              <td><span class="status-pill s-pending">EN ATTENTE</span></td>
              <td style="font-family:var(--mono);font-size:.72rem;color:var(--muted)">26/03/26</td>
            </tr>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="u-avatar" style="background:#7B1FA2">M</div>
                  <div>
                    <div class="u-name">Mohamed Ghali</div>
                    <div class="u-email">m.ghali@mail.com</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--muted);font-size:.78rem">Grocery & Budget</td>
              <td style="font-family:var(--mono);font-size:.8rem;color:var(--primary)">21</td>
              <td><span class="status-pill s-active">ACTIF</span></td>
              <td style="font-family:var(--mono);font-size:.72rem;color:var(--muted)">25/03/26</td>
            </tr>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="u-avatar" style="background:#E53935">R</div>
                  <div>
                    <div class="u-name">Rana Khelifi</div>
                    <div class="u-email">rana.k@mail.com</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--muted);font-size:.78rem">Food & Nutrition</td>
              <td style="font-family:var(--mono);font-size:.8rem;color:var(--muted)">0</td>
              <td><span class="status-pill s-inactive">INACTIF</span></td>
              <td style="font-family:var(--mono);font-size:.72rem;color:var(--muted)">24/03/26</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Right: Side panels -->
    <div class="side-panels">

      <!-- Module usage -->
      <div class="mini-card">
        <div class="card-header" style="margin-bottom:.5rem">
          <div>
            <div class="card-title">Utilisation par module</div>
            <div class="card-sub">% d'utilisateurs actifs</div>
          </div>
        </div>
        <div class="module-usage">
          <div class="module-row">
            <div class="module-info"><span class="module-name">👤 User & Profile</span><span class="module-pct">96%</span></div>
            <div class="prog-bar"><div class="prog-fill" style="width:96%;background:#3F51B5"></div></div>
          </div>
          <div class="module-row">
            <div class="module-info"><span class="module-name">🥗 Food & Nutrition</span><span class="module-pct">81%</span></div>
            <div class="prog-bar"><div class="prog-fill" style="width:81%;background:#009688"></div></div>
          </div>
          <div class="module-row">
            <div class="module-info"><span class="module-name">📅 Meal Planning</span><span class="module-pct">74%</span></div>
            <div class="prog-bar"><div class="prog-fill" style="width:74%;background:#F57C00"></div></div>
          </div>
          <div class="module-row">
            <div class="module-info"><span class="module-name">📈 Suivi & Statistiques</span><span class="module-pct">68%</span></div>
            <div class="prog-bar"><div class="prog-fill" style="width:68%;background:#E53935"></div></div>
          </div>
          <div class="module-row">
            <div class="module-info"><span class="module-name">🛒 Grocery</span><span class="module-pct">52%</span></div>
            <div class="prog-bar"><div class="prog-fill" style="width:52%;background:#7B1FA2"></div></div>
          </div>
          <div class="module-row">
            <div class="module-info"><span class="module-name">📖 Recipes</span><span class="module-pct">41%</span></div>
            <div class="prog-bar"><div class="prog-fill" style="width:41%;background:#00796B"></div></div>
          </div>
        </div>
      </div>

      <!-- Activity feed -->
      <div class="mini-card">
        <div class="card-header" style="margin-bottom:0">
          <div>
            <div class="card-title">Activité récente</div>
            <div class="card-sub">Dernières actions</div>
          </div>
        </div>
        <div class="activity-list">
          <div class="activity-item">
            <div class="act-dot" style="background:var(--primary)"></div>
            <div>
              <div class="act-text">Nouvel aliment ajouté : <strong>Tofu fumé</strong></div>
              <div class="act-time">Il y a 4 min</div>
            </div>
          </div>
          <div class="activity-item">
            <div class="act-dot" style="background:var(--gold)"></div>
            <div>
              <div class="act-text">Recette en attente de modération</div>
              <div class="act-time">Il y a 17 min</div>
            </div>
          </div>
          <div class="activity-item">
            <div class="act-dot" style="background:var(--blue)"></div>
            <div>
              <div class="act-text"><strong>38</strong> nouveaux utilisateurs cette semaine</div>
              <div class="act-time">Aujourd'hui</div>
            </div>
          </div>
          <div class="activity-item">
            <div class="act-dot" style="background:var(--purple)"></div>
            <div>
              <div class="act-text">Plan IA généré pour 12 utilisateurs</div>
              <div class="act-time">Il y a 1h</div>
            </div>
          </div>
          <div class="activity-item">
            <div class="act-dot" style="background:var(--red)"></div>
            <div>
              <div class="act-text">Compte inactif détecté — Rana K.</div>
              <div class="act-time">Il y a 3h</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom Grid -->
  <div class="bottom-grid">

    <!-- Top aliments -->
    <div class="chart-card">
      <div class="card-header">
        <div>
          <div class="card-title">🏆 Top aliments recherchés</div>
          <div class="card-sub">Cette semaine</div>
        </div>
      </div>
      <div style="margin-top:.5rem">
        <div class="food-row">
          <div class="food-rank" style="background:rgba(249,168,37,.2);color:var(--gold)">1</div>
          <span class="food-name">🥚 Œuf entier</span>
          <span class="food-count">342 fois</span>
        </div>
        <div class="food-row">
          <div class="food-rank" style="background:rgba(61,186,82,.1)">2</div>
          <span class="food-name">🍗 Blanc de poulet</span>
          <span class="food-count">289 fois</span>
        </div>
        <div class="food-row">
          <div class="food-rank">3</div>
          <span class="food-name">🥦 Brocoli</span>
          <span class="food-count">241 fois</span>
        </div>
        <div class="food-row">
          <div class="food-rank">4</div>
          <span class="food-name">🌾 Flocons d'avoine</span>
          <span class="food-count">198 fois</span>
        </div>
        <div class="food-row">
          <div class="food-rank">5</div>
          <span class="food-name">🥑 Avocat</span>
          <span class="food-count">176 fois</span>
        </div>
      </div>
    </div>

    <!-- Budget stats -->
    <div class="chart-card">
      <div class="card-header">
        <div>
          <div class="card-title">💰 Budget moyen / utilisateur</div>
          <div class="card-sub">Module Grocery & Budget</div>
        </div>
      </div>
      <div class="budget-display">
        <div class="budget-big">42,80 €</div>
        <div class="budget-sub">Budget hebdomadaire moyen</div>
        <div style="font-size:.75rem;color:var(--primary);margin-top:.3rem">↓ −5% vs semaine dernière</div>
      </div>
      <div class="budget-progress">
        <div class="budget-fill"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:.68rem;color:var(--muted);margin-top:.4rem;font-family:var(--mono)">
        <span>0 €</span><span>Cible : 50 €</span><span>100 €</span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-top:1rem">
        <div style="background:var(--border);border-radius:.6rem;padding:.7rem;text-align:center">
          <div style="font-family:var(--mono);font-size:1.1rem;font-weight:700;color:var(--primary)">68%</div>
          <div style="font-size:.68rem;color:var(--muted)">utilisent le module</div>
        </div>
        <div style="background:var(--border);border-radius:.6rem;padding:.7rem;text-align:center">
          <div style="font-family:var(--mono);font-size:1.1rem;font-weight:700;color:var(--lime)">193</div>
          <div style="font-size:.68rem;color:var(--muted)">listes créées</div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="chart-card">
      <div class="card-header">
        <div>
          <div class="card-title">⚡ Actions rapides</div>
          <div class="card-sub">Administration</div>
        </div>
      </div>
      <div class="quick-actions">
        <button class="qa-btn">
          <div class="qa-icon">➕</div>
          <div class="qa-label">Ajouter aliment</div>
        </button>
        <button class="qa-btn">
          <div class="qa-icon">✅</div>
          <div class="qa-label">Valider recettes</div>
        </button>
        <button class="qa-btn">
          <div class="qa-icon">📧</div>
          <div class="qa-label">Notifier users</div>
        </button>
        <button class="qa-btn">
          <div class="qa-icon">📊</div>
          <div class="qa-label">Rapport PDF</div>
        </button>
        <button class="qa-btn">
          <div class="qa-icon">🔄</div>
          <div class="qa-label">Sync base IA</div>
        </button>
        <button class="qa-btn">
          <div class="qa-icon">🗑️</div>
          <div class="qa-label">Purger cache</div>
        </button>
      </div>
    </div>

    </div>
  </div>

  <!-- Suivi et Statistiques View -->
  <div id="view-suivi" class="content-view">
    <div class="page-header">
      <div>
        <div class="page-title">Gestion Suivi et Statistiques</div>
        <div class="page-sub">Surveillance des performances utilisateurs et logs caloriques</div>
      </div>
      <div class="header-actions">
        <button onclick="exportAdminDashboard()" class="btn-sm btn-ghost">📊 Exporter Rapport PDF</button>
        <button class="btn-sm btn-green">⚙️ Configurer AI</button>
      </div>
    </div>

    <!-- Admin Stats for Suivi -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top">
          <div class="kpi-label">Volume de données</div>
          <div class="kpi-icon" style="background:rgba(41,182,246,.12)">📁</div>
        </div>
        <div class="kpi-val">128 GB</div>
        <div class="kpi-trend up">↑ +2.4GB aujourd'hui</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top">
          <div class="kpi-label">Sessions actives</div>
          <div class="kpi-icon" style="background:rgba(61,186,82,.12)">🟢</div>
        </div>
        <div class="kpi-val">84</div>
        <div class="kpi-trend up">↑ Utilisateurs en ligne</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top">
          <div class="kpi-label">Calories Totales Loggées</div>
          <div class="kpi-icon" style="background:rgba(249,168,37,.12)">🔥</div>
        </div>
        <div class="kpi-val">1.2M</div>
        <div class="kpi-trend up">↑ Ce mois-ci</div>
      </div>
    </div>

    <div class="main-grid" style="grid-template-columns: 1fr;">
      <div class="chart-card">
        <div class="card-header" style="flex-wrap: wrap; gap: 10px;">
          <div>
            <div class="card-title">Flux de Suivi Utilisateur (Gestion Directe)</div>
            <div class="card-sub">Gérez tous les logs de progression</div>
          </div>
          
          <!-- Metier: Recherche et Tri (Backend) -->
          <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; margin-left: auto;">
            <input type="hidden" name="view" value="view-suivi">
            <input type="text" name="search" placeholder="Rechercher (ex: Pomme)..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 0.5rem; border-radius: 0.5rem; border: 1px solid var(--border); background: var(--bg); color: white; outline: none;">
            <select name="sort" style="padding: 0.5rem; border-radius: 0.5rem; border: 1px solid var(--border); background: var(--bg); color: white; outline: none;">
                <option value="date DESC" <?php if($sort=='date DESC') echo 'selected'; ?>>Plus récents</option>
                <option value="date ASC" <?php if($sort=='date ASC') echo 'selected'; ?>>Plus anciens</option>
                <option value="calories DESC" <?php if($sort=='calories DESC') echo 'selected'; ?>>Calories (Max)</option>
                <option value="calories ASC" <?php if($sort=='calories ASC') echo 'selected'; ?>>Calories (Min)</option>
            </select>
            <button type="submit" class="btn-sm btn-ghost">Filtrer</button>
          </form>

          <button class="btn-sm btn-green" onclick="showAddModal()">+ Ajouter un Log</button>
        </div>
        <table class="users-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Utilisateur</th>
              <th>Action / Description</th>
              <th>Impact</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = $logs->fetch(PDO::FETCH_ASSOC)): ?>
            <tr id="admin-log-<?php echo $row['id']; ?>">
              <td>#<?php echo $row['id']; ?></td>
              <td>USR-<?php echo $row['user_id']; ?></td>
              <td><strong><?php echo htmlspecialchars($row['description']); ?></strong><br><small><?php echo ucfirst($row['type']); ?></small></td>
              <td style="color:<?php echo $row['type']=='meal' ? 'var(--red)' : ($row['type']=='weight' ? 'var(--gold)' : 'var(--primary)'); ?>">
                <?php 
                  if($row['type'] == 'weight') echo htmlspecialchars($row['description']);
                  else echo ($row['type'] == 'meal' ? '+' : '-') . $row['calories'] . ' kcal'; 
                ?>
              </td>
              <td><?php echo $row['date']; ?></td>
              <td>
                <button onclick="editLog(<?php echo $row['id']; ?>, '<?php echo $row['type']; ?>', '<?php echo addslashes($row['description']); ?>', <?php echo $row['calories']; ?>)" style="background:none; border:none; cursor:pointer;">✏️</button>
                <button onclick="deleteLogAdmin(<?php echo $row['id']; ?>, '<?php echo $row['type']; ?>')" style="background:none; border:none; cursor:pointer;">🗑️</button>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script>
  // Tab switching
  document.querySelectorAll('.chart-tab').forEach(tab => {
    tab.addEventListener('click', function() {
      this.closest('.chart-tabs').querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
    });
  });

  // Sidebar active state
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
      if (this.href && this.href.includes('.html')) return;
      e.preventDefault();
      document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
      this.classList.add('active');
    });
  });
</script>

<!-- ADMIN MODAL -->
<div id="logModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Ajouter un Log</h3>
      <span class="close-modal" onclick="closeModal()">&times;</span>
    </div>
    <form id="adminLogForm" class="admin-form">
      <input type="hidden" name="action" id="adminFormAction" value="create">
      <input type="hidden" name="id" id="adminLogId" value="">
      <input type="hidden" name="user_id" value="1">
      
      <div class="form-group">
        <label>Type de log</label>
        <select name="type" id="adminLogType" class="admin-input">
          <option value="meal">Repas (Aliment)</option>
          <option value="activity">Activité (Sport)</option>
          <option value="weight">Journal de Poids (Second entité)</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Description (MIN 3 caract.)</label>
        <input type="text" name="description" id="adminLogDesc" class="admin-input" placeholder="Ex: Poulet, Running...">
      </div>
      
      <div class="form-group" id="caloriesGroup">
        <label>Calories (kcal)</label>
        <input type="number" name="calories" id="adminLogCals" class="admin-input" placeholder="Ex: 300">
      </div>

      <div class="form-group" id="weightGroup" style="display:none">
        <label>Valeur du Poids (kg)</label>
        <input type="number" step="0.1" name="weight" id="adminLogWeight" class="admin-input" placeholder="Ex: 72.5">
      </div>
      
      <button type="submit" class="btn-sm btn-green" style="margin-top: 1rem; width: 100%; padding: .8rem;">Confirmer</button>
    </form>
  </div>
</div>

<!-- ADMIN DELETE MODAL -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 400px; text-align: center; background: white; color: #333; padding: 2rem; border-radius: 1.5rem;">
        <div class="modal-header" style="border-bottom: none; justify-content: center; padding-bottom: 0.5rem;">
            <h3 class="modal-title" id="deleteModalTitle" style="color: #2c4c3e; font-size: 1.4rem;">Confirmer la suppression ?</h3>
        </div>
        <p style="margin: 1rem 0; color: #666; font-size: 0.95rem; line-height: 1.4;">Voulez-vous vraiment supprimer cet enregistrement du système ? Cette action est définitive.</p>
        <div style="display: flex; gap: 12px; justify-content: center; margin-top: 1.5rem;">
            <button id="confirmDeleteBtn" class="btn-sm" style="background: #e74c3c; color: white; border: none; width: 130px; height: 40px; border-radius: 0.6rem; cursor: pointer; font-weight: 500;">Supprimer</button>
            <button onclick="closeDeleteModal()" class="btn-sm" style="background: #f1f1f1; color: #555; border: 1px solid #ddd; width: 130px; height: 40px; border-radius: 0.6rem; cursor: pointer;">Annuler</button>
        </div>
    </div>
</div>

<script>
    // Tab switching
    document.querySelectorAll('.chart-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            this.closest('.chart-tabs').querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Sidebar active state
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (this.href && this.href.includes('.html')) return;
            e.preventDefault();
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const modal = document.getElementById('logModal');
    
    function showAddModal() {
        document.getElementById('modalTitle').innerText = "Ajouter un Log";
        document.getElementById('adminFormAction').value = "create";
        document.getElementById('adminLogId').value = "";
        document.getElementById('adminLogForm').reset();
        document.getElementById('caloriesGroup').style.display = 'flex';
        document.getElementById('weightGroup').style.display = 'none';
        modal.style.display = 'grid';
    }

    function editLog(id, type, desc, cals) {
        document.getElementById('modalTitle').innerText = "Modifier le Log #" + id;
        document.getElementById('adminFormAction').value = "update";
        document.getElementById('adminLogId').value = id;
        document.getElementById('adminLogType').value = type;
        
        // Handle field visibility
        if (type === 'weight') {
            document.getElementById('caloriesGroup').style.display = 'none';
            document.getElementById('weightGroup').style.display = 'flex';
            // Parse weight from description "75.5 kg (...)"
            const weightVal = parseFloat(desc.split(' ')[0]) || 70;
            document.getElementById('adminLogWeight').value = weightVal;
        } else {
            document.getElementById('caloriesGroup').style.display = 'flex';
            document.getElementById('weightGroup').style.display = 'none';
            document.getElementById('adminLogCals').value = cals;
        }
        
        document.getElementById('adminLogDesc').value = desc;
        modal.style.display = 'grid';
    }

    // Type switching listener
    document.getElementById('adminLogType').addEventListener('change', function() {
        const type = this.value;
        if (type === 'weight') {
            document.getElementById('caloriesGroup').style.display = 'none';
            document.getElementById('weightGroup').style.display = 'flex';
        } else {
            document.getElementById('caloriesGroup').style.display = 'flex';
            document.getElementById('weightGroup').style.display = 'none';
        }
    });

    function closeModal() { modal.style.display = 'none'; }
    let deleteTarget = { id: null, type: null };
    const dModal = document.getElementById('deleteModal');

    function deleteLogAdmin(id, type) {
        deleteTarget = { id, type };
        dModal.style.display = 'grid';
    }

    function closeDeleteModal() { dModal.style.display = 'none'; }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if(!deleteTarget.id) return;
        
        const params = new URLSearchParams();
        params.append('action', 'delete');
        params.append('id', deleteTarget.id);
        params.append('type', deleteTarget.type);

        fetch('../../controllers/SuiviController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') location.reload();
            else alert('Erreur: ' + data.message);
        })
        .catch(err => alert("Erreur de communication avec le serveur."))
        .finally(() => closeDeleteModal());
    });

    // Close modals on outside click
    window.onclick = function(event) { 
        if (event.target == modal) closeModal(); 
        if (event.target == dModal) closeDeleteModal();
    }

    document.getElementById('adminLogForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Contrôle de saisie
        const desc = document.getElementById('adminLogDesc').value.toLowerCase();
        const disallowed = ['car', 'voiture', 'avion', 'plane'];
        if (disallowed.some(word => desc.includes(word))) {
            alert("Erreur: Veuillez entrer une description d'aliment ou d'activité valide.");
            return;
        }

        const formData = new FormData(this);
        formData.append('date', new Date().toISOString().split('T')[0]);
        
        // Auto-fill description for Weight if empty
        if (formData.get('type') === 'weight' && !formData.get('description')) {
            formData.set('description', formData.get('weight') + ' kg (Manuel)');
        }

        if (!formData.get('description')) {
            alert("Veuillez entrer une description.");
            return;
        }
        
        fetch('../../controllers/SuiviController.php', { method: 'POST', body: formData })
        .then(r => r.text()) // Get raw text first to debug potential crashes
        .then(text => {
            try {
                const data = JSON.parse(text);
                if(data.status === 'success') location.reload();
                else alert('Erreur: ' + data.message);
            } catch(e) {
                console.error("Server Error:", text);
                alert("Erreur serveur lors de l'enregistrement.");
            }
        });
    });
    async function exportAdminDashboard() {
        const { jsPDF } = window.jspdf;
        const element = document.getElementById('view-suivi');
        
        // Hide UI elements not needed in PDF
        const actions = document.querySelectorAll('.users-table td:last-child, .header-actions');
        actions.forEach(a => a.style.opacity = '0');
        
        try {
            const canvas = await html2canvas(element, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#0d1210' // Match dark theme
            });
            
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('l', 'mm', 'a4');
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
            
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('Rapport_Admin_NutriSmart.pdf');
        } catch (err) {
            console.error(err);
            alert("Erreur lors de la génération du PDF.");
        } finally {
            actions.forEach(a => a.style.opacity = '1');
        }
    }
</script>
</body>
</html>
