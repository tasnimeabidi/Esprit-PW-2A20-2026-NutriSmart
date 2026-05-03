<?php
require_once '../../Services/BudgetService.php';
require_once '../../Services/UserService.php';
$budgetService = new BudgetService();
$userService = new UserService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
        
        switch ($_POST['action']) {
            case 'create':
            case 'update':
                $userId = $_POST['user_id_select'] ?? $_POST['user_id'];
                $success = $budgetService->setBudget($userId, $_POST['montant']);
                if ($isAjax) {
             
                    echo $success ? 'success' : 'error';
                    exit;
                }
                break;
            case 'delete':
                $budgetService->deleteBudget($_POST['user_id']);
                if ($isAjax) {
                    echo 'success';
                    exit;
                }
                break;
        }
        header('Location: budget-admin.php');
        exit;
    }
}

$budgets = $budgetService->getAllBudgets();
$users = $userService->getAllUsers();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NutriSmart — Budget & Courses</title>

 
  <link rel="stylesheet" href="../../css/mp-dashboard.css" />
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
            <button class="btn-sm btn-green" type="button" onclick="openModal('create')">+ Ajouter un budget</button>
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
                <td>
                  <span class="budget-amount" data-user-id="<?php echo $budget['id_utilisateur']; ?>" onclick="editBudgetInline(this)"><?php echo htmlspecialchars($budget['montant']); ?></span> TND
                </td>
                <td><?php echo htmlspecialchars($budget['total_depense']); ?> TND</td>
                <td style="color:<?php echo $budget['total_depense'] > $budget['montant'] ? 'red' : 'green'; ?>;">
                  <?php echo $budget['total_depense'] > $budget['montant'] ? 'Dépassement' : 'OK'; ?>
                </td>
                <td>
                  <button class="btn-sm btn-ghost" onclick="triggerInlineEdit(<?php echo $budget['id_utilisateur']; ?>)">Modifier</button>
                  <button class="btn-sm btn-ghost" style="color: red;" onclick="deleteBudget(<?php echo $budget['id_utilisateur']; ?>, '<?php echo htmlspecialchars($budget['user_nom']); ?>')">Supprimer</button>
                  <button class="btn-ghost" onclick="viewPurchases(<?php echo $budget['id_utilisateur']; ?>)">Voir achats</button>
                </td>
              </tr>
              <?php endforeach; ?>
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
      purchases.forEach(purchase => {
        const safeAlimentName = String(purchase.aliment_nom).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        const row = `<tr>
          <td>${purchase.aliment_nom}</td>
          <td>${purchase.quantite}</td>
          <td>${purchase.prix_total} TND</td>
          <td>${purchase.date_achat}</td>
          <td>
            <button class="btn-sm btn-ghost" onclick="showPurchaseEditor(${purchase.id}, ${purchase.quantite}, ${purchase.prix_total})">Modifier</button>
            <button class="btn-sm btn-ghost" style="color:red;" onclick="showDeleteModal(${purchase.id}, '${safeAlimentName}')">Supprimer</button>
          </td>
        </tr>`;
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
      
   
      input.addEventListener('blur', function() {
        saveBudget(userId, input.value, span);
      });
      
  
      input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          saveBudget(userId, input.value, span);
        }
      });
      
  
      input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          span.classList.remove('editing');
          span.textContent = currentAmount;
        }
      });
    }
    
    function saveBudget(userId, newAmount, span) {
      console.log('saveBudget called with userId:', userId, 'newAmount:', newAmount);
      const formData = new FormData();
      formData.append('action', 'update');
      formData.append('user_id', userId);
      formData.append('montant', newAmount);
      formData.append('ajax', '1'); // Add ajax flag
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('Response status:', response.status);
        return response.text();
      })
      .then(text => {
        console.log('Response text:', text);
        if (text.includes('success') || text.trim() === '' || text.includes('Budget')) {
          span.classList.remove('editing');
          span.textContent = parseFloat(newAmount).toFixed(2);
          console.log('Budget updated successfully');
        } else {
          throw new Error('Unexpected response: ' + text.substring(0, 100));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        span.classList.remove('editing');
        span.textContent = parseFloat(newAmount).toFixed(2);
        alert('Erreur lors de la mise à jour du budget.');
      });
    }

    function viewPurchases(userId) {
      currentPurchasesUserId = userId;
      fetch('get_purchases.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
          document.getElementById('user-name').textContent = data.user_name;
          currentPurchasesData = data.purchases || [];
          renderPurchases();
          document.getElementById('purchases-section').style.display = 'block';
        });
    }

    function showPurchaseEditor(id, quantite, prixTotal) {
      console.log('showPurchaseEditor', { id, quantite, prixTotal });
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

      console.log('submitPurchaseEdit payload', {
        id: purchaseId,
        quantite: quantite,
        prix_total: prixTotal
      });
      fetch('./update_purchase.php', {
        method: 'POST',
        body: bodyData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('HTTP error ' + response.status);
        }
        return response.json();
      })
      .then(data => {
        console.log('update_purchase response', data);
        if (data.success) {
          hidePurchaseEditor();
          viewPurchases(currentPurchasesUserId);
        } else {
          alert('Erreur lors de la mise à jour : ' + (data.error || 'Veuillez réessayer.') + '\nValeurs reçues : ' + JSON.stringify(data.received || {}));
        }
      })
      .catch(error => {
        console.error('Erreur lors de la mise à jour de l\'achat', error);
        alert('Erreur lors de la mise à jour de l\'achat. Vérifiez la console du navigateur.');
      });
      return false;
    }

    function removePurchase(purchaseId) {
      fetch('./delete_purchase.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: purchaseId })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          viewPurchases(currentPurchasesUserId);
        } else {
          alert('Erreur lors de la suppression : ' + (data.error || 'Veuillez réessayer.'));
        }
      });
    }
    function openModal(mode, userId = null, montant = '') {
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
      console.log('triggerInlineEdit called with userId:', userId);
      const span = document.querySelector(`.budget-amount[data-user-id="${userId}"]`);
      console.log('Found span:', span);
      if (span) {
        editBudgetInline(span);
      } else {
        console.error('Span not found for userId:', userId);
        alert('Erreur: Impossible de trouver le champ à modifier');
      }
    }

    window.addEventListener('DOMContentLoaded', function() {
      const budgetModal = document.getElementById('budget-modal');
      if (budgetModal) {
        budgetModal.addEventListener('click', function(e) {
          if (e.target === this) {
            closeModal();
          }
        });
      }

      const purchasesTbody = document.getElementById('purchases-tbody');
      if (purchasesTbody) {
        purchasesTbody.addEventListener('click', function(e) {
          const button = e.target.closest('button[data-action]');
          if (!button) return;
          if (button.dataset.action === 'edit') {
            showPurchaseEditor(button.dataset.purchaseId, button.dataset.quantite, button.dataset.prixTotal);
          }
        });
      }
    });
  </script>

<div id="budget-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
  <div style="background: white; padding: 2rem; border-radius: 8px; width: 400px; max-width: 90%;">
    <h2 id="modal-title" style="margin-top: 0; color: #3dba52;">Ajouter un budget</h2>
    <form id="modal-form" method="POST" action="">
      <input type="hidden" id="action" name="action" value="create">
      <input type="hidden" id="user_id" name="user_id" value="">
      <div style="margin-bottom: 1rem;">
        <label for="user_select" style="display: block; margin-bottom: 0.5rem;">Utilisateur</label>
        <select id="user_select" name="user_id_select" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
          <option value="">Sélectionner un utilisateur</option>
          <?php foreach ($users as $user): ?>
            <option value="<?php echo $user['id_utilisateur']; ?>"><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></option>
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
        <input id="edit-quantite" name="quantite" type="number" min="1" required style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:4px;">
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

<form id="delete-form" method="POST" action="" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="user_id" value="">
</form>

</body>
</html>