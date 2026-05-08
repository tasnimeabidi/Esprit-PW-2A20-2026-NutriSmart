<?php
include_once '../../controllers/RecetteController.php';
$controller = new RecetteController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_recette') {
            $controller->createRecette($_POST);
        } elseif ($_POST['action'] === 'delete_recette') {
            $controller->deleteRecette($_POST['id']);
        } elseif ($_POST['action'] === 'update_recette') {
            $controller->updateRecette($_POST);
        }
    }
    header("Location: recettes.php");
    exit();
}

$recettes = $controller->listRecettes();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>NutriSmart — Gestion des Recettes</title>
    <link rel="stylesheet" href="../../css/mp-dashboard.css" />
    <link rel="stylesheet" href="backoffice-shell.css" />
    <script src="../../js/recette-validation.js" defer></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #3dba52, #8bc34a);
        }

        .bo-global-dash-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .bo-table-wrap {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 1rem;
            margin-bottom: 2rem;
            border: 1px solid var(--bo-border);
        }

        .bo-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .bo-table th {
            color: var(--bo-muted);
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem;
            border-bottom: 2px solid var(--bo-bg);
        }

        .bo-table td {
            padding: 1rem;
            color: var(--bo-text);
            font-size: 0.9rem;
            border-bottom: 1px solid var(--bo-bg);
            vertical-align: middle;
        }

        .bo-table tr:hover td {
            background-color: #f9fbf9;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-grid;
            place-items: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-edit { background: #e3f2fd; color: #1976d2; }
        .btn-edit:hover { background: #1976d2; color: white; }
        .btn-delete { background: #ffebee; color: #c62828; margin-left: 5px; }
        .btn-delete:hover { background: #c62828; color: white; }

        .bo-form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .bo-field span {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--bo-muted);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .bo-field input, .bo-field textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            border: 1px solid var(--bo-border);
            background: #fdfdfd;
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }

        .bo-field input:focus, .bo-field textarea:focus {
            border-color: var(--bo-primary);
            outline: none;
        }

        .bo-form-submit {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(61, 186, 82, 0.3);
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(26, 50, 40, 0.4);
            backdrop-filter: blur(4px);
            z-index: 1000;
            padding: 2rem;
        }

        .modal-content {
            background: white;
            max-width: 600px;
            margin: 2rem auto;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--bo-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body { padding: 2rem; }

        .close-btn { font-size: 1.5rem; cursor: pointer; color: var(--bo-muted); }
    </style>
</head>
<body class="bo-shell-body">
    <!-- TOPBAR (Identique à Aliments pour la cohérence) -->
    <header class="topbar">
        <a href="nutrismart-dashboard.html" class="topbar-logo">
            <svg width="32" height="32" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible">
                <mask id="biteMask">
                    <rect x="-20" y="-20" width="140" height="140" fill="white" />
                    <circle cx="92" cy="35" r="18" fill="black" />
                    <circle cx="84" cy="62" r="14" fill="black" />
                </mask>
                <g mask="url(#biteMask)">
                    <path d="M 20 80 C 35 45 65 25 90 10 C 90 60 70 90 20 80 Z" fill="#3dba52" />
                    <path d="M 20 80 C 10 30 40 10 90 10 C 65 25 35 45 20 80 Z" fill="#8bc34a" />
                </g>
                <path d="M 22 78 L 12 92" stroke="#3dba52" stroke-width="7" stroke-linecap="round" />
            </svg>
            <div style="display: flex; align-items: center; gap: 2px">
                <span style="color: #3dba52">Nutri</span><span style="color: #8bc34a">Smart</span>
            </div>
            <span class="logo-badge">ADMIN</span>
        </a>
        <div class="topbar-right">
            <div class="notif-btn" title="Notifications">
                🔔
                <div class="notif-dot"></div>
            </div>
            <div class="notif-btn" title="Paramètres">⚙️</div>
            <div class="admin-avatar">
                <img src="images/chahine.png" alt="Chahine Chaieb" class="avatar-img">
                <div class="admin-info">
                    <div class="admin-name">Chahine Chaieb</div>
                    <div class="admin-role">Super Administrator</div>
                </div>
            </div>
        </div>
    </header>

    <!-- SIDEBAR -->
    <aside class="bo-admin-sidebar" aria-label="Navigation administration">
        <div class="nav-section-label">Principal</div>
        <a class="nav-item" href="nutrismart-dashboard.html"><span class="nav-icon">📊</span> Tableau de bord</a>
        <a class="nav-item" href="users.html">
            <span class="nav-icon">👥</span> Utilisateurs
            <span class="nav-badge">284</span>
        </a>
        <a class="nav-item active" href="aliment.php">
            <span class="nav-icon">🥗</span> Aliment-Recette
            <span class="nav-badge warn">12</span>
        </a>
        <a class="nav-item" href="planRepas.html">
            <span class="nav-icon">📅</span> planRepas
        </a>
        <a class="nav-item" href="../frontoffice/suivi-statistiques.php">
            <span class="nav-icon">📈</span> Progressions
        </a>
        <a class="nav-item" href="#">
            <span class="nav-icon">🛒</span> Courses &amp; Budget
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
            <a class="nav-item" href="../frontoffice/nutrismart-website.html" style="color: #3dba52">
                <span class="nav-icon">🌐</span> Voir le site
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="bo-shell-main">
        <div class="app app--embed-dash">
            <div class="main dash-workspace">
                <header class="bo-global-dash-head">
                    <div>
                        <h1 class="serif">Gestion des Recettes</h1>
                        <p class="bo-global-dash-meta">Créez et gérez vos recettes saines.</p>
                    </div>
                    <a href="#ajouter-recette" class="btn-pill-orange">+ Nouvelle recette</a>
                </header>

                <!-- Liste des Recettes -->
                <div class="bo-table-wrap">
                    <table class="bo-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom de la Recette</th>
                                <th>Instructions</th>
                                <th>Énergie Totale</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recettes as $rec): ?>
                            <tr>
                                <td style="font-family: var(--bo-mono); opacity: 0.6;">#<?= $rec['id'] ?></td>
                                <td style="font-weight: 700;"><?= htmlspecialchars($rec['nom_recette']) ?></td>
                                <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color: var(--bo-muted);">
                                    <?= htmlspecialchars($rec['instructions']) ?>
                                </td>
                                <td><strong><?= $rec['calories_totales'] ?></strong> <small>kcal</small></td>
                                <td style="text-align:right;">
                                    <button class="btn-action btn-edit" onclick='openEditModal(<?= json_encode($rec) ?>)' title="Modifier">✎</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_recette">
                                        <input type="hidden" name="id" value="<?= $rec['id'] ?>">
                                        <button type="submit" class="btn-action btn-delete" onclick="return confirm('Supprimer cette recette ?');" title="Supprimer">✕</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Formulaire d'ajout -->
                <section class="panel-card" id="ajouter-recette">
                    <div class="panel-head">
                        <h2 class="serif">Créer une Recette</h2>
                    </div>
                    <form class="bo-form-grid" action="recettes.php" method="post">
                        <input type="hidden" name="action" value="add_recette">
                        <label class="bo-field">
                            <span>Nom de la recette</span>
                            <input type="text" name="nom_recette" placeholder="Ex: Salade Quinoa, Bowl d'automne..." required />
                        </label>
                        <label class="bo-field">
                            <span>Instructions (étapes)</span>
                            <textarea name="instructions" placeholder="Décrivez les étapes de préparation..." rows="4"></textarea>
                        </label>
                        <label class="bo-field">
                            <span>Calories Totales</span>
                            <input type="number" step="0.1" name="calories_totales" placeholder="0.0" required />
                        </label>
                        <label class="bo-field">
                            <span>Lien Vidéo (TikTok, YouTube...)</span>
                            <input type="url" name="video_url" placeholder="https://..." />
                        </label>
                        <button type="submit" class="bo-form-submit">Enregistrer la recette</button>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <!-- MODAL D'ÉDITION -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="serif" style="margin:0;">Modifier la recette</h2>
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form class="bo-form-grid" action="recettes.php" method="post">
                    <input type="hidden" name="action" value="update_recette">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="status" id="edit_status">
                    
                    <label class="bo-field">
                        <span>Nom</span>
                        <input type="text" name="nom_recette" id="edit_nom" required />
                    </label>
                    <label class="bo-field">
                        <span>Instructions</span>
                        <textarea name="instructions" id="edit_instructions" rows="4"></textarea>
                    </label>
                    <label class="bo-field">
                        <span>Calories Totales</span>
                        <input type="number" step="0.1" name="calories_totales" id="edit_calories" required />
                    </label>
                    <label class="bo-field">
                        <span>Lien Vidéo</span>
                        <input type="url" name="video_url" id="edit_video" placeholder="https://..." />
                    </label>
                    <button type="submit" class="bo-form-submit">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(rec) {
            document.getElementById('edit_id').value = rec.id;
            document.getElementById('edit_nom').value = rec.nom_recette;
            document.getElementById('edit_instructions').value = rec.instructions;
            document.getElementById('edit_calories').value = rec.calories_totales;
            document.getElementById('edit_video').value = rec.video_url || '';
            document.getElementById('edit_status').value = rec.status || 'pending';
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) closeEditModal();
        }
    </script>
</body>
</html>
