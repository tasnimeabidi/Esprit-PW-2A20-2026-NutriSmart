<?php
require_once '../../Services/BudgetService.php';
require_once '../../Services/UserService.php';

$budgetService = new BudgetService();
$budgets = $budgetService->getAllBudgets();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NutriSmart — Budget & Courses</title>

 
  <link rel="stylesheet" href="../../css/mp-dashboard.css" />
    <link rel="stylesheet" href="../../css/shared-styles.css" />
     <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="backoffice-shell.css" />
</head>

<body class="bo-shell-body">

  
  <header class="topbar">
    <a href="nutrismart-dashboard.html" class="topbar-logo">
      <span style="color:#3dba52">Nutri</span><span style="color:#8bc34a">Smart</span>
      <span class="logo-badge">ADMIN</span>
    </a>

    <div class="topbar-right">
      <div class="notif-btn">🔔</div>
      <div class="notif-btn">⚙️</div>
      <div class="admin-avatar">
        <div class="avatar-img">A</div>
        <div class="admin-info">
          <div class="admin-name">Admin Principal</div>
          <div class="admin-role">Super Administrator</div>
        </div>
      </div>
    </div>
  </header>

  
  <aside class="bo-admin-sidebar" aria-label="Navigation administration">
    <div class="nav-section-label">Principal</div>

    <a class="nav-item" href="nutrismart-dashboard.html">
      <span class="nav-icon">📊</span> Tableau de bord
    </a>
    <a class="nav-item" href="users.html">
      <span class="nav-icon">👥</span> Utilisateurs
      <span class="nav-badge">284</span>
    </a>
    <a class="nav-item" href="aliment.php">
      <span class="nav-icon">🥗</span> Aliments
      <span class="nav-badge warn">12</span>
    </a>
    <a class="nav-item" href="planRepas.html">
      <span class="nav-icon">📅</span> planRepas
    </a>
    <a class="nav-item" href="progression.html">
      <span class="nav-icon">📈</span> Progressions
    </a>
    <a class="nav-item active" href="budget-admin.php">
      <span class="nav-icon">🛒</span> Courses & Budget
    </a>
    <a class="nav-item" href="recettes.php">
      <span class="nav-icon">📖</span> Recettes
      <span class="nav-badge warn">5</span>
    </a>

    <div class="nav-section-label">Données</div>
    <a class="nav-item" href="#">
      <span class="nav-icon">📉</span> Statistiques
    </a>
    <a class="nav-item" href="#">
      <span class="nav-icon">📤</span> Exports
    </a>
    <a class="nav-item" href="#">
      <span class="nav-icon">🗄️</span> Base de données
    </a>

    <div class="nav-section-label">Système</div>
    <a class="nav-item" href="#">
      <span class="nav-icon">🔒</span> Permissions
    </a>
    <a class="nav-item" href="#">
      <span class="nav-icon">📋</span> Logs d'activité
    </a>
    <a class="nav-item" href="#">
      <span class="nav-icon">⚙️</span> Paramètres
    </a>

    <div style="margin-top: auto; padding-top: 1.5rem">
      <a class="nav-item" href="../frontoffice/plan-repas.html" style="color: #3dba52">
        <span class="nav-icon">🌐</span> Front office
      </a>
    </div>
  </aside>

  
  <main class="bo-shell-main">
    <div class="app app--embed-dash">
      <div class="main dash-workspace">

        <header class="bo-progression-head">
          <div>
            <h1 class="serif">Budget & Courses</h1>
            <p class="bo-progression-meta">Gestion intelligente des dépenses alimentaires</p>
          </div>

          <div class="bo-progression-actions">
            <button class="btn-sm btn-ghost">Exporter</button>
            <button class="btn-green">+ Générer liste</button>
          </div>
        </header>

        <section class="metrics-row">
          <div class="metric-card border-forest">
            <div>
              <div class="label">Utilisateurs actifs</div>
              <div class="value"><?php echo count($budgets); ?></div>
            </div>
          </div>

          <div class="metric-card border-mint">
            <div>
              <div class="label">Listes générées</div>
              <div class="value">890</div>
            </div>
          </div>

          <div class="metric-card border-orange">
            <div>
              <div class="label">Économie moyenne</div>
              <div class="value">-23%</div>
            </div>
          </div>
        </section>

        <section class="panel-card" style="margin-top:20px;">
          <h3 class="serif">Suivi des budgets utilisateurs</h3>

          <table class="bo-prog-users-table">
            <thead>
              <tr>
                <th>Utilisateur</th>
                <th>Budget</th>
                <th>Dépenses</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($budgets as $budget): ?>
              <tr>
                <td><?php echo htmlspecialchars($budget['user_nom']); ?></td>
                <td><?php echo htmlspecialchars($budget['montant']); ?> TND</td>
                <td><?php echo htmlspecialchars($budget['total_depense']); ?> TND</td>
                <td style="color:<?php echo $budget['total_depense'] > $budget['montant'] ? 'red' : 'green'; ?>;">
                  <?php echo $budget['total_depense'] > $budget['montant'] ? 'Dépassement' : 'OK'; ?>
                </td>
                <td><button class="btn-ghost" onclick="viewPurchases(<?php echo $budget['id_utilisateur']; ?>)">Voir achats</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>

        <section class="panel-card" id="purchases-section" style="display:none; margin-top:20px;">
          <h3 class="serif">Achats de l'utilisateur: <span id="user-name"></span></h3>
          <table class="bo-prog-users-table">
            <thead>
              <tr>
                <th>Aliment</th>
                <th>Quantité</th>
                <th>Prix Total</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody id="purchases-tbody">
            </tbody>
          </table>
        </section>

      </div>
    </div>
  </main>

  <script>
    function viewPurchases(userId) {
      fetch('get_purchases.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
          document.getElementById('user-name').textContent = data.user_name;
          const tbody = document.getElementById('purchases-tbody');
          tbody.innerHTML = '';
          data.purchases.forEach(purchase => {
            const row = `<tr>
              <td>${purchase.aliment_nom}</td>
              <td>${purchase.quantite}</td>
              <td>${purchase.prix_total} TND</td>
              <td>${purchase.date_achat}</td>
            </tr>`;
            tbody.innerHTML += row;
          });
          document.getElementById('purchases-section').style.display = 'block';
        });
    }
  </script>

</body>
</html>