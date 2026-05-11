<?php
declare(strict_types=1);

if (!defined('NUTRISMART_BO_WEB')) {
    define('NUTRISMART_BO_WEB', '');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$__budgetAdminSelf = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/Views/backoffice/budget-admin.php'));
$__scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$__httpHost = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$__fullSelf = $__scheme . $__httpHost . $__budgetAdminSelf;
$__budgetLoginNext = NUTRISMART_BO_WEB !== '' ? '../frontoffice/budget-admin.php' : '../backoffice/budget-admin.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../frontoffice/login.html?next=' . rawurlencode($__budgetLoginNext));
    exit;
}

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../Services/BudgetService.php';
require_once __DIR__ . '/../../Services/UserService.php';

$budgetService = new BudgetService();
$userService = new UserService();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

    switch ($_POST['action']) {
        case 'create':
        case 'update':
            $userIdRaw = $_POST['user_id_select'] ?? $_POST['user_id'] ?? '';
            $userId = (int) $userIdRaw;
            $montantRaw = $_POST['montant'] ?? '';
            if ($userId <= 0 || $montantRaw === '' || !is_numeric((string) $montantRaw)) {
                if ($isAjax) {
                    echo 'error';
                    exit;
                }
                break;
            }
            $success = $budgetService->setBudget($userId, (float) $montantRaw);
            if ($isAjax) {
                echo $success ? 'success' : 'error';
                exit;
            }
            break;
        case 'delete':
            $delId = (int) ($_POST['user_id'] ?? 0);
            if ($delId > 0) {
                $budgetService->deleteBudget($delId);
            }
            if ($isAjax) {
                echo 'success';
                exit;
            }
            break;
    }
    $redir = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? 'budget-admin.php'));
    header('Location: ' . $redir);
    exit;
}

$__boH = NUTRISMART_BO_WEB !== '' ? '../backoffice/' : '';
$__shellCss = NUTRISMART_BO_WEB !== '' ? '../backoffice/backoffice-shell.css' : 'backoffice-shell.css';
$__budgetHref = NUTRISMART_BO_WEB !== '' ? '../frontoffice/budget-admin.php' : 'budget-admin.php';

$budgets = $budgetService->getAllBudgets();
$users = $userService->getAllUsers();

function bo_user_label(array $user): string
{
    $nom = trim((string) ($user['nom'] ?? ''));
    $pre = trim((string) ($user['prenom'] ?? ''));
    $label = trim($nom . ' ' . $pre);

    return $label !== '' ? $label : (string) ($user['email'] ?? 'Utilisateur');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NutriSmart — Budget &amp; courses</title>
  <link rel="stylesheet" href="../frontoffice/css/mp-dashboard.css" />
  <link rel="stylesheet" href="<?php echo htmlspecialchars($__shellCss, ENT_QUOTES, 'UTF-8'); ?>" />
  <script>
    window.__NUTRISMART_BO_WEB = <?php echo json_encode(NUTRISMART_BO_WEB, JSON_UNESCAPED_SLASHES); ?>;
    window.__NUTRISMART_BUDGET_POST = <?php echo json_encode($__fullSelf, JSON_UNESCAPED_SLASHES); ?>;
  </script>
</head>

<body class="bo-shell-body">

  <header class="topbar">
    <a href="<?php echo htmlspecialchars($__boH . 'nutrismart-dashboard.html', ENT_QUOTES, 'UTF-8'); ?>" class="topbar-logo">
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

    <a class="nav-item" href="<?php echo htmlspecialchars($__boH . 'nutrismart-dashboard.html', ENT_QUOTES, 'UTF-8'); ?>">
      <span class="nav-icon">📊</span> Tableau de bord
    </a>
    <a class="nav-item" href="<?php echo htmlspecialchars($__boH . 'users.html', ENT_QUOTES, 'UTF-8'); ?>">
      <span class="nav-icon">👥</span> Utilisateurs
      <span class="nav-badge">284</span>
    </a>
    <a class="nav-item" href="<?php echo htmlspecialchars($__boH . 'aliment.php', ENT_QUOTES, 'UTF-8'); ?>">
      <span class="nav-icon">🥗</span> Aliments
      <span class="nav-badge warn">12</span>
    </a>
    <a class="nav-item" href="<?php echo htmlspecialchars($__boH . 'planRepas.html', ENT_QUOTES, 'UTF-8'); ?>">
      <span class="nav-icon">📅</span> planRepas
    </a>
    <a class="nav-item" href="<?php echo htmlspecialchars($__boH . 'progression.html', ENT_QUOTES, 'UTF-8'); ?>">
      <span class="nav-icon">📈</span> Progressions
    </a>
    <a class="nav-item active" href="<?php echo htmlspecialchars($__budgetHref, ENT_QUOTES, 'UTF-8'); ?>">
      <span class="nav-icon">🛒</span> Courses &amp; Budget
    </a>
    <a class="nav-item" href="<?php echo htmlspecialchars($__boH . 'recettes.php', ENT_QUOTES, 'UTF-8'); ?>">
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
      <div class="main dash-workspace bo-global-dash">

        <header class="bo-progression-head">
          <div>
            <h1 class="serif">Budget &amp; courses</h1>
            <p class="bo-progression-meta">Gestion intelligente des dépenses alimentaires</p>
          </div>

          <div class="bo-progression-actions">
            <button type="button" class="btn-sm btn-ghost">Exporter</button>
            <button class="btn-sm btn-green" type="button" onclick="openModal('create')">+ Ajouter un budget</button>
          </div>
        </header>

        <section class="metrics-row">
          <div class="metric-card border-forest">
            <div>
              <div class="label">Utilisateurs avec budget</div>
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
              <?php if (count($budgets) === 0) { ?>
              <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--bo-muted);">Aucun budget enregistré.</td></tr>
              <?php } else { ?>
              <?php foreach ($budgets as $budget) {
                  $dep = (float) ($budget['total_depense'] ?? 0);
                  $mnt = (float) ($budget['montant'] ?? 0);
                  $uid = (int) ($budget['id_utilisateur'] ?? 0);
                  ?>
              <tr>
                <td><?php echo htmlspecialchars((string) ($budget['user_nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <span class="budget-amount" data-user-id="<?php echo $uid; ?>" onclick="editBudgetInline(this)"><?php echo htmlspecialchars(number_format($mnt, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?></span> TND
                </td>
                <td><?php echo htmlspecialchars(number_format($dep, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?> TND</td>
                <td style="color:<?php echo $dep > $mnt ? 'red' : 'green'; ?>;">
                  <?php echo $dep > $mnt ? 'Dépassement' : 'OK'; ?>
                </td>
                <td>
                  <button type="button" class="btn-sm btn-ghost" onclick="triggerInlineEdit(<?php echo $uid; ?>)">Modifier</button>
                  <button type="button" class="btn-sm btn-ghost" style="color: red;" onclick="deleteBudget(<?php echo $uid; ?>, <?php echo json_encode((string) ($budget['user_nom'] ?? ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)">Supprimer</button>
                  <button type="button" class="btn-ghost" onclick="viewPurchases(<?php echo $uid; ?>)">Voir achats</button>
                </td>
              </tr>
              <?php } ?>
              <?php } ?>
            </tbody>
          </table>
        </section>

        <style>
          .budget-amount {
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
            transition: background-color 0.2s;
          }
          .budget-amount:hover {
            background-color: #e8f5e9;
          }
          .budget-amount.editing {
            background-color: #fff3e0;
            padding: 0;
          }
          .budget-amount input {
            width: 100px;
            padding: 2px 6px;
            border: 2px solid #3dba52;
            border-radius: 4px;
            font-size: inherit;
            text-align: center;
          }
        </style>

        <section class="panel-card" id="purchases-section" style="display:none; margin-top:20px;">
          <h3 class="serif">Achats de l'utilisateur: <span id="user-name"></span></h3>
          <div style="display:flex; gap:0.75rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
            <label for="purchase-sort" style="font-weight:600; margin:0;">Trier par :</label>
            <select id="purchase-sort" onchange="changePurchaseSort(this.value)" style="padding:0.5rem; border:1px solid #ddd; border-radius:4px;">
              <option value="date" selected>Date</option>
              <option value="quantite">Quantité</option>
              <option value="prix_total">Prix Total</option>
            </select>
            <button type="button" id="purchase-sort-order" onclick="togglePurchaseSortDirection()" style="padding:0.5rem 0.75rem; border:none; border-radius:4px; background:#3dba52; color:white; cursor:pointer;">Desc</button>
          </div>
          <table class="bo-prog-users-table">
            <thead>
              <tr>
                <th>Aliment</th>
                <th>Quantité</th>
                <th>Prix Total</th>
                <th>Date</th>
                <th>Actions</th>
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
    let currentPurchasesUserId = null;
    let currentPurchasesData = [];
    let currentPurchasesSort = { field: 'date', direction: 'desc' };

    function escapeHtml(str) {
      if (str == null) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function changePurchaseSort(field) {
      currentPurchasesSort.field = field;
      renderPurchases();
    }

    function togglePurchaseSortDirection() {
      currentPurchasesSort.direction = currentPurchasesSort.direction === 'asc' ? 'desc' : 'asc';
      document.getElementById('purchase-sort-order').textContent = currentPurchasesSort.direction === 'asc' ? 'Asc' : 'Desc';
      renderPurchases();
    }

    function renderPurchases() {
      const tbody = document.getElementById('purchases-tbody');
      tbody.innerHTML = '';
      const purchases = [...currentPurchasesData];
      purchases.sort((a, b) => {
        let field = currentPurchasesSort.field;
        if (field === 'date') field = 'date_achat';

        let aValue = a[field];
        let bValue = b[field];
        if (currentPurchasesSort.field === 'date') {
          aValue = new Date(aValue || '1970-01-01');
          bValue = new Date(bValue || '1970-01-01');
        } else {
          aValue = Number(aValue || 0);
          bValue = Number(bValue || 0);
        }
        if (aValue < bValue) return currentPurchasesSort.direction === 'asc' ? -1 : 1;
        if (aValue > bValue) return currentPurchasesSort.direction === 'asc' ? 1 : -1;
        return 0;
      });
      purchases.forEach(function (purchase) {
        const id = Number(purchase.id);
        const q = Number(purchase.quantite);
        const p = Number(purchase.prix_total);
        const safeAlimentName = String(purchase.aliment_nom).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        const row = '<tr>' +
          '<td>' + escapeHtml(purchase.aliment_nom) + '</td>' +
          '<td>' + escapeHtml(purchase.quantite) + '</td>' +
          '<td>' + escapeHtml(purchase.prix_total) + ' TND</td>' +
          '<td>' + escapeHtml(purchase.date_achat) + '</td>' +
          '<td>' +
          '<button type="button" class="btn-sm btn-ghost" onclick="showPurchaseEditor(' + id + ', ' + q + ', ' + p + ')">Modifier</button> ' +
          '<button type="button" class="btn-sm btn-ghost" style="color:red;" onclick="showDeleteModal(' + id + ', \'' + safeAlimentName + '\')">Supprimer</button>' +
          '</td>' +
        '</tr>';
        tbody.innerHTML += row;
      });
    }

    function editBudgetInline(span) {
      const userId = span.dataset.userId;
      const currentAmount = parseFloat(span.textContent).toFixed(2);

      const input = document.createElement('input');
      input.type = 'number';
      input.step = '0.01';
      input.min = '0';
      input.value = currentAmount;
      input.className = 'budget-input';

      span.classList.add('editing');
      span.textContent = '';
      span.appendChild(input);
      input.focus();
      input.select();

      input.addEventListener('blur', function () {
        saveBudget(userId, input.value, span);
      });

      input.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
          saveBudget(userId, input.value, span);
        }
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          span.classList.remove('editing');
          span.textContent = currentAmount;
        }
      });
    }

    function saveBudget(userId, newAmount, span) {
      const formData = new FormData();
      formData.append('action', 'update');
      formData.append('user_id', userId);
      formData.append('montant', newAmount);
      formData.append('ajax', '1');

      fetch(window.__NUTRISMART_BUDGET_POST || 'budget-admin.php', {
        method: 'POST',
        body: formData
      })
      .then(function (response) { return response.text(); })
      .then(function (text) {
        var t = (text || '').trim();
        if (t === 'success') {
          span.classList.remove('editing');
          span.textContent = parseFloat(newAmount).toFixed(2);
        } else {
          throw new Error(t);
        }
      })
      .catch(function () {
        span.classList.remove('editing');
        span.textContent = parseFloat(newAmount).toFixed(2);
        alert('Erreur lors de la mise à jour du budget.');
      });
    }

    function viewPurchases(userId) {
      currentPurchasesUserId = userId;
      fetch((window.__NUTRISMART_BO_WEB || '') + 'get_purchases.php?user_id=' + encodeURIComponent(userId))
        .then(function (response) { return response.json(); })
        .then(function (data) {
          document.getElementById('user-name').textContent = data.user_name || '';
          currentPurchasesData = data.purchases || [];
          renderPurchases();
          document.getElementById('purchases-section').style.display = 'block';
        })
        .catch(function () {
          alert('Impossible de charger les achats.');
        });
    }

    function showPurchaseEditor(id, quantite, prixTotal) {
      document.getElementById('edit-purchase-id').value = id;
      document.getElementById('edit-quantite').value = quantite;
      document.getElementById('edit-prix-total').value = prixTotal;
      document.getElementById('purchase-edit-modal').style.display = 'flex';
      document.getElementById('edit-quantite').focus();
    }

    function hidePurchaseEditor() {
      document.getElementById('purchase-edit-modal').style.display = 'none';
      document.getElementById('edit-purchase-form').reset();
    }

    function showDeleteModal(purchaseId, alimentName) {
      window.purchaseToDelete = purchaseId;
      document.getElementById('delete-purchase-message').textContent = 'Supprimer l\'achat de "' + alimentName + '" ?';
      document.getElementById('purchase-delete-modal').style.display = 'flex';
    }

    function hideDeleteModal() {
      window.purchaseToDelete = null;
      document.getElementById('purchase-delete-modal').style.display = 'none';
    }

    function confirmDeletePurchase() {
      if (!window.purchaseToDelete) return;
      const purchaseId = window.purchaseToDelete;
      hideDeleteModal();
      removePurchase(purchaseId);
    }

    function submitPurchaseEdit() {
      const purchaseId = document.getElementById('edit-purchase-id').value;
      const quantite = document.getElementById('edit-quantite').value;
      const prixTotal = document.getElementById('edit-prix-total').value;
      const bodyData = new URLSearchParams();
      bodyData.append('id', purchaseId);
      bodyData.append('quantite', quantite);
      bodyData.append('prix_total', prixTotal);

      fetch((window.__NUTRISMART_BO_WEB || '') + 'update_purchase.php', {
        method: 'POST',
        body: bodyData
      })
      .then(function (response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
      })
      .then(function (data) {
        if (data.success) {
          hidePurchaseEditor();
          viewPurchases(currentPurchasesUserId);
        } else {
          alert('Erreur lors de la mise à jour : ' + (data.error || 'Veuillez réessayer.'));
        }
      })
      .catch(function () {
        alert('Erreur lors de la mise à jour de l\'achat.');
      });
      return false;
    }

    function removePurchase(purchaseId) {
      fetch((window.__NUTRISMART_BO_WEB || '') + 'delete_purchase.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: purchaseId })
      })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.success) {
          viewPurchases(currentPurchasesUserId);
        } else {
          alert('Erreur lors de la suppression : ' + (data.error || 'Veuillez réessayer.'));
        }
      });
    }

    function openModal(mode, userId, montant) {
      userId = userId || null;
      montant = montant || '';
      document.getElementById('modal-title').textContent = mode === 'create' ? 'Ajouter un budget' : 'Modifier un budget';
      document.getElementById('action').value = mode;

      if (mode === 'edit') {
        document.getElementById('user_id').value = userId;
        document.getElementById('user_id').required = true;
        document.getElementById('user_select').value = userId;
        document.getElementById('user_select').disabled = true;
        document.getElementById('user_select').required = false;
        document.getElementById('montant').value = montant;
      } else {
        document.getElementById('user_id').value = '';
        document.getElementById('user_id').required = false;
        document.getElementById('user_select').disabled = false;
        document.getElementById('user_select').value = '';
        document.getElementById('user_select').required = true;
        document.getElementById('montant').value = '';
      }

      document.getElementById('budget-modal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('budget-modal').style.display = 'none';
    }

    function deleteBudget(userId, userName) {
      if (confirm('Êtes-vous sûr de vouloir supprimer le budget de ' + userName + ' ?')) {
        const form = document.getElementById('delete-form');
        form.querySelector('[name="user_id"]').value = userId;
        form.submit();
      }
    }

    function triggerInlineEdit(userId) {
      const span = document.querySelector('.budget-amount[data-user-id="' + userId + '"]');
      if (span) {
        editBudgetInline(span);
      } else {
        alert('Erreur: Impossible de trouver le champ à modifier');
      }
    }

    window.addEventListener('DOMContentLoaded', function () {
      const budgetModal = document.getElementById('budget-modal');
      if (budgetModal) {
        budgetModal.addEventListener('click', function (e) {
          if (e.target === this) {
            closeModal();
          }
        });
      }
    });
  </script>

<div id="budget-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
  <div style="background: white; padding: 2rem; border-radius: 8px; width: 400px; max-width: 90%;">
    <h2 id="modal-title" style="margin-top: 0; color: #3dba52;">Ajouter un budget</h2>
    <form id="modal-form" method="POST" action="<?php echo htmlspecialchars($__budgetAdminSelf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" id="action" name="action" value="create">
      <input type="hidden" id="user_id" name="user_id" value="">
      <div style="margin-bottom: 1rem;">
        <label for="user_select" style="display: block; margin-bottom: 0.5rem;">Utilisateur</label>
        <select id="user_select" name="user_id_select" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
          <option value="">Sélectionner un utilisateur</option>
          <?php foreach ($users as $user): ?>
            <option value="<?php echo (int) ($user['id_utilisateur'] ?? 0); ?>"><?php echo htmlspecialchars(bo_user_label($user), ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="margin-bottom: 1.5rem;">
        <label for="montant" style="display: block; margin-bottom: 0.5rem;">Budget (TND)</label>
        <input type="number" id="montant" name="montant" step="0.01" min="0" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
      </div>
      <div style="display: flex; gap: 1rem; justify-content: flex-end;">
        <button type="button" onclick="closeModal()" style="padding: 0.5rem 1rem; border: none; background: #ccc; border-radius: 4px; cursor: pointer;">Annuler</button>
        <button type="submit" style="padding: 0.5rem 1rem; border: none; background: #3dba52; color: white; border-radius: 4px; cursor: pointer;">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<div id="purchase-edit-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1200; justify-content:center; align-items:center;">
  <div style="background:white; border-radius:12px; padding:2rem; width:420px; max-width:90%; box-shadow:0 16px 40px rgba(0,0,0,0.18);">
    <h3 class="serif" style="margin-top:0;">Modifier achat</h3>
    <form id="edit-purchase-form" onsubmit="return submitPurchaseEdit()" style="display:grid; gap:1rem;">
      <input type="hidden" id="edit-purchase-id" name="purchase_id" value="">
      <div style="display:grid; gap:0.5rem;">
        <label for="edit-quantite">Quantité</label>
        <input id="edit-quantite" name="quantite" type="number" min="0.01" step="0.01" required style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:4px;">
      </div>
      <div style="display:grid; gap:0.5rem;">
        <label for="edit-prix-total">Prix total (TND)</label>
        <input id="edit-prix-total" name="prix_total" type="number" step="0.01" min="0" required style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:4px;">
      </div>
      <div style="display:flex; gap:1rem; justify-content:flex-end;">
        <button type="button" class="btn-sm btn-ghost" onclick="hidePurchaseEditor()">Annuler</button>
        <button type="submit" class="btn-sm btn-green">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<div id="purchase-delete-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1200; justify-content:center; align-items:center;">
  <div style="background:white; border-radius:12px; padding:2rem; width:400px; max-width:90%; box-shadow:0 16px 40px rgba(0,0,0,0.18);">
    <h3 class="serif" style="margin-top:0;">Confirmer la suppression</h3>
    <p id="delete-purchase-message" style="margin-bottom:1.5rem;">Voulez-vous vraiment supprimer cet achat ?</p>
    <div style="display:flex; gap:1rem; justify-content:flex-end;">
      <button type="button" class="btn-sm btn-ghost" onclick="hideDeleteModal()">Annuler</button>
      <button type="button" class="btn-sm btn-green" onclick="confirmDeletePurchase()">Supprimer</button>
    </div>
  </div>
</div>

<form id="delete-form" method="POST" action="<?php echo htmlspecialchars($__budgetAdminSelf, ENT_QUOTES, 'UTF-8'); ?>" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="user_id" value="">
</form>

</body>
</html>
