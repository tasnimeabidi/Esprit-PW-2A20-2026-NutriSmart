<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Guard: admin only
if (!isset($_SESSION['id_utilisateur']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /ProjetNutrismart/index.php?action=login"); exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../Models/Publication.php';
require_once __DIR__ . '/../../Models/Commentaire.php';
require_once __DIR__ . '/../../Models/Notification.php';

$db          = (new Database())->connect();
$postModel   = new Publication($db);
$commentModel= new Commentaire($db);
$notifModel  = new Notification($db);

/* ── active panel ── */
$panel = $_GET['panel'] ?? 'dashboard';

/* ========================= ADMIN ACTIONS ========================= */

/* Delete a post */
if (isset($_GET['delete_post'])) {
    $postModel->delete((int)$_GET['delete_post']);
    header("Location: /ProjetNutrismart/index.php?action=admin_dashboard&panel=blog&msg=post_deleted"); exit;
}

/* Delete a comment */
if (isset($_GET['delete_comment'])) {
    $commentModel->delete((int)$_GET['delete_comment']);
    header("Location: /ProjetNutrismart/index.php?action=admin_dashboard&panel=blog&msg=comment_deleted"); exit;
}

/* Warn a user (send notification) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['warn_user'])) {
    $targetUserId = (int)$_POST['target_user_id'];
    $warnMsg      = trim($_POST['warn_message'] ?? '');
    if ($targetUserId && $warnMsg !== '') {
        $notifModel->create($targetUserId, "⚠️ Avertissement de l'administrateur : " . $warnMsg);
    }
    header("Location: /ProjetNutrismart/index.php?action=admin_dashboard&panel=blog&msg=warned"); exit;
}

/* ── fetch data for blog panel ── */
$allPosts    = $postModel->getAllAdmin();
$allComments = [];
if ($panel === 'blog') {
    $stmt = $db->query("
        SELECT c.*, u.nom, p.titre AS post_titre
        FROM commentaire c
        JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
        JOIN publication p ON c.id_publication = p.id_publication
        ORDER BY c.date_commentaire DESC
    ");
    $allComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ── fetch all users for warn dropdown ── */
$stmt  = $db->query("SELECT id_utilisateur, nom, email FROM utilisateur WHERE role != 'admin' ORDER BY nom");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── flash message ── */
$flashMap = [
    'post_deleted'    => ['type' => 'success', 'text' => '✅ Publication supprimée.'],
    'comment_deleted' => ['type' => 'success', 'text' => '✅ Commentaire supprimé.'],
    'warned'          => ['type' => 'success', 'text' => '✅ Avertissement envoyé à l\'utilisateur.'],
];
$flash = $flashMap[$_GET['msg'] ?? ''] ?? null;

/* ── base admin URL ── */
$base = '/ProjetNutrismart/index.php?action=admin_dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>NutriSmart — Tableau de bord Admin</title>
    <link rel="stylesheet" href="/ProjetNutrismart/Views/backoffice/backoffice.css"/>
    <link rel="stylesheet" href="/ProjetNutrismart/Views/backoffice/backoffice-shell.css"/>
    <style>
        /* ── BLOG PANEL STYLES ── */
        .blog-panel { padding: 1.5rem 1.75rem 3rem; }

        .bp-flash {
            padding: 0.75rem 1.1rem;
            border-radius: 0.7rem;
            margin-bottom: 1.2rem;
            font-size: 0.88rem;
            font-weight: 600;
        }
        .bp-flash.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .bp-flash.error   { background: #fdecea; color: #c62828; border: 1px solid #ef9a9a; }

        .bp-section-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--bo-text, #1a3228);
            margin: 1.8rem 0 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .bp-count {
            font-size: 0.72rem;
            font-weight: 700;
            background: var(--bo-primary, #3dba52);
            color: white;
            padding: 0.15rem 0.5rem;
            border-radius: 2rem;
        }

        .bp-search {
            display: flex;
            gap: 0.6rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .bp-search input {
            flex: 1;
            min-width: 180px;
            padding: 0.55rem 1rem;
            border-radius: 2rem;
            border: 1.5px solid #ddd;
            font-size: 0.88rem;
            font-family: inherit;
            outline: none;
        }
        .bp-search input:focus { border-color: var(--bo-primary, #3dba52); }

        .warn-box {
            background: #fff8e1;
            border: 1.5px solid #ffe082;
            border-radius: 1rem;
            padding: 1.2rem 1.4rem;
            margin-bottom: 2rem;
        }
        .warn-box h4 {
            font-size: 0.95rem;
            font-weight: 800;
            color: #e65100;
            margin-bottom: 0.8rem;
        }
        .warn-box form {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .warn-box select,
        .warn-box input[type="text"] {
            padding: 0.55rem 0.9rem;
            border-radius: 0.6rem;
            border: 1.5px solid #ffd54f;
            font-size: 0.88rem;
            font-family: inherit;
            background: white;
            outline: none;
        }
        .warn-box select        { min-width: 180px; }
        .warn-box input[type="text"] { flex: 1; min-width: 220px; }
        .warn-box button {
            padding: 0.55rem 1.2rem;
            border-radius: 2rem;
            border: none;
            background: #f57c00;
            color: white;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s;
        }
        .warn-box button:hover { background: #e65100; }

        .bp-table-wrap { overflow-x: auto; border-radius: 1rem; border: 1px solid #e5ebe7; }
        .bp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
            background: white;
        }
        .bp-table thead th {
            background: #f4f7f5;
            text-align: left;
            padding: 0.7rem 1rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #5c6b62;
            border-bottom: 1px solid #e5ebe7;
        }
        .bp-table tbody td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f0f4f2;
            color: #1a3228;
            vertical-align: middle;
        }
        .bp-table tbody tr:last-child td { border-bottom: none; }
        .bp-table tbody tr:hover td { background: #f9fbfa; }
        .bp-table .cell-title {
            font-weight: 700;
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .bp-table .cell-content {
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #5c6b62;
        }

        .bp-pill { display: inline-block; font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 2rem; }
        .bp-pill-green { background: #e8f5e9; color: #2e7d32; }
        .bp-pill-blue  { background: #e3f2fd; color: #1565c0; }
        .bp-pill-gray  { background: #f3f4f6; color: #555; }

        .bp-action-btns { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        .bp-btn-delete {
            display: inline-flex; align-items: center; gap: 0.2rem;
            padding: 0.3rem 0.75rem; border-radius: 2rem;
            font-size: 0.78rem; font-weight: 700;
            background: #fdecea; color: #c62828;
            border: 1.5px solid #ef9a9a;
            text-decoration: none; transition: all 0.15s; cursor: pointer;
        }
        .bp-btn-delete:hover { background: #ffcdd2; transform: translateY(-1px); }
        .bp-btn-warn {
            display: inline-flex; align-items: center; gap: 0.2rem;
            padding: 0.3rem 0.75rem; border-radius: 2rem;
            font-size: 0.78rem; font-weight: 700;
            background: #fff8e1; color: #e65100;
            border: 1.5px solid #ffe082;
            cursor: pointer; transition: all 0.15s;
            font-family: inherit;
        }
        .bp-btn-warn:hover { background: #ffecb3; transform: translateY(-1px); }

        .warn-modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .warn-modal-overlay.open { display: flex; }
        .warn-modal {
            background: white;
            border-radius: 1.2rem;
            padding: 2rem;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .warn-modal h3 { font-size: 1rem; font-weight: 800; color: #e65100; margin-bottom: 1rem; }
        .warn-modal label { display: block; font-size: 0.82rem; font-weight: 700; color: #1a3228; margin-bottom: 0.35rem; }
        .warn-modal textarea {
            width: 100%; padding: 0.7rem 0.9rem;
            border-radius: 0.7rem; border: 1.5px solid #ffd54f;
            font-family: inherit; font-size: 0.88rem;
            resize: vertical; min-height: 90px; outline: none; margin-bottom: 1rem;
        }
        .warn-modal textarea:focus { border-color: #f57c00; }
        .warn-modal-btns { display: flex; gap: 0.6rem; justify-content: flex-end; }
        .warn-modal-btns .cancel {
            padding: 0.55rem 1.1rem; border-radius: 2rem;
            border: 1.5px solid #ddd; background: white;
            font-weight: 700; font-size: 0.85rem; cursor: pointer; font-family: inherit;
        }
        .warn-modal-btns .send {
            padding: 0.55rem 1.2rem; border-radius: 2rem;
            border: none; background: #f57c00; color: white;
            font-weight: 700; font-size: 0.85rem; cursor: pointer; font-family: inherit;
        }
        .warn-modal-btns .send:hover { background: #e65100; }

        .bp-empty {
            text-align: center; padding: 2.5rem; color: #aaa; font-size: 0.95rem;
            background: white; border-radius: 1rem; border: 1px dashed #ddd;
        }
        .bp-empty span { font-size: 2rem; display: block; margin-bottom: 0.5rem; }

        .bp-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .bp-stat { background: white; border: 1px solid #e5ebe7; border-radius: 1rem; padding: 1rem 1.2rem; }
        .bp-stat-label { font-size: 0.72rem; font-weight: 700; color: #5c6b62; margin-bottom: 0.3rem; letter-spacing: 0.05em; }
        .bp-stat-value { font-size: 1.8rem; font-weight: 800; color: #1a3228; }
        .bp-stat-sub   { font-size: 0.75rem; color: #3dba52; font-weight: 600; margin-top: 0.2rem; }
    </style>
</head>
<body class="bo-shell-body">

<!-- ═══════════════════ TOPBAR ═══════════════════ -->
<header class="topbar">
    <a href="<?= $base ?>&panel=dashboard" class="topbar-logo">
        <svg width="32" height="32" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow:visible">
            <mask id="biteMaskDash">
                <rect x="-20" y="-20" width="140" height="140" fill="white"/>
                <circle cx="92" cy="35" r="18" fill="black"/>
                <circle cx="84" cy="62" r="14" fill="black"/>
            </mask>
            <g mask="url(#biteMaskDash)">
                <path d="M 20 80 C 35 45 65 25 90 10 C 90 60 70 90 20 80 Z" fill="#3dba52"/>
                <path d="M 20 80 C 10 30 40 10 90 10 C 65 25 35 45 20 80 Z" fill="#8bc34a"/>
            </g>
            <path d="M 22 78 L 12 92" stroke="#3dba52" stroke-width="7" stroke-linecap="round"/>
        </svg>
        <div style="display:flex;align-items:center;gap:2px">
            <span style="color:#3dba52">Nutri</span><span style="color:#8bc34a">Smart</span>
        </div>
        <span class="logo-badge">ADMIN</span>
    </a>
    <div class="topbar-right">
        <div class="notif-btn" title="Notifications">🔔<div class="notif-dot"></div></div>
        <div class="notif-btn" title="Paramètres">⚙️</div>
        <div class="admin-avatar">
            <div class="avatar-img"><?= strtoupper(substr($_SESSION['nom'] ?? 'A', 0, 1)) ?></div>
            <div class="admin-info">
                <div class="admin-name"><?= htmlspecialchars($_SESSION['nom'] ?? 'Admin') ?></div>
                <div class="admin-role">Super Administrator</div>
            </div>
        </div>
    </div>
</header>

<!-- ═══════════════════ SIDEBAR ═══════════════════ -->
<aside class="bo-admin-sidebar" aria-label="Navigation administration">
    <div class="nav-section-label">Principal</div>
    <a class="nav-item <?= $panel === 'dashboard' ? 'active' : '' ?>"
       href="<?= $base ?>&panel=dashboard">
        <span class="nav-icon">📊</span> Tableau de bord
    </a>
    <a class="nav-item" href="users.html">
        <span class="nav-icon">👥</span> Utilisateurs
        <span class="nav-badge"><?= count($users) ?></span>
    </a>
    <a class="nav-item <?= $panel === 'blog' ? 'active' : '' ?>"
       href="<?= $base ?>&panel=blog">
        <span class="nav-icon">📝</span> Gestion Blog
        <span class="nav-badge warn"><?= count($allPosts) ?></span>
    </a>
    <a class="nav-item" href="aliments.html">
        <span class="nav-icon">🥗</span> Aliments
        <span class="nav-badge warn">12</span>
    </a>
    <a class="nav-item" href="planRepas.html">
        <span class="nav-icon">📅</span> Plan Repas
    </a>
    <a class="nav-item" href="progression.html">
        <span class="nav-icon">📈</span> Progressions
    </a>
    <a class="nav-item" href="#">
        <span class="nav-icon">🛒</span> Courses &amp; Budget
    </a>
    <a class="nav-item" href="#">
        <span class="nav-icon">📖</span> Recettes
        <span class="nav-badge warn">5</span>
    </a>
    <a class="nav-item" href="favorie-recettes.html">
        <span class="nav-icon">⭐</span> Recettes Favoris
    </a>

    <div class="nav-section-label">Données</div>
    <a class="nav-item" href="#"><span class="nav-icon">📉</span> Statistiques</a>
    <a class="nav-item" href="#"><span class="nav-icon">📤</span> Exports</a>
    <a class="nav-item" href="#"><span class="nav-icon">🗄️</span> Base de données</a>

    <div class="nav-section-label">Système</div>
    <a class="nav-item" href="#"><span class="nav-icon">🔒</span> Permissions</a>
    <a class="nav-item" href="#"><span class="nav-icon">📋</span> Logs d'activité</a>
    <a class="nav-item" href="#"><span class="nav-icon">⚙️</span> Paramètres</a>

    <div style="margin-top:auto;padding-top:1.5rem">
        <a class="nav-item" href="/ProjetNutrismart/index.php?action=blog" style="color:#3dba52">
            <span class="nav-icon">🌐</span> Front office
        </a>
        <a class="nav-item" href="/ProjetNutrismart/index.php?action=logout" style="color:#c62828">
            <span class="nav-icon">🚪</span> Déconnexion
        </a>
    </div>
</aside>

<!-- ═══════════════════ MAIN ═══════════════════ -->
<main class="bo-shell-main">

<?php if ($panel === 'dashboard'): ?>
<!-- ══════════ DASHBOARD PANEL ══════════ -->
<div class="app app--embed-dash">
    <div class="main dash-workspace bo-global-dash">
        <header class="bo-global-dash-head">
            <div>
                <h1 class="serif">Tableau de bord</h1>
                <p class="bo-global-dash-meta"><?= date('d/m/Y') ?> · Connecté en tant qu'admin</p>
            </div>
            <div class="bo-global-dash-actions">
                <button type="button" class="btn-sm btn-ghost">Exporter rapport</button>
                <a class="btn-sm btn-green" href="<?= $base ?>&panel=blog">📝 Gestion Blog</a>
            </div>
        </header>

        <section class="metrics-row" aria-label="Indicateurs clés">
            <div class="metric-card border-forest">
                <div class="metric-ico f">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                </div>
                <div>
                    <div class="label">Utilisateurs actifs</div>
                    <div class="value metric-value--split">
                        <span class="metric-num"><?= count($users) ?></span>
                        <span class="metric-suffix metric-suffix--ok">utilisateurs</span>
                    </div>
                </div>
            </div>
            <div class="metric-card border-mint">
                <div class="metric-ico m">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                        <path d="M8 12h8M12 8v8"/>
                    </svg>
                </div>
                <div>
                    <div class="label">Publications</div>
                    <div class="value metric-value--split">
                        <span class="metric-num"><?= count($allPosts) ?></span>
                        <span class="metric-suffix metric-suffix--ok">posts actifs</span>
                    </div>
                </div>
            </div>
            <div class="metric-card border-orange">
                <div class="metric-ico o">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="label">Aliments indexés</div>
                    <div class="value metric-value--split">
                        <span class="metric-num">1 247</span>
                        <span class="metric-suffix metric-suffix--ok">+43 cette semaine</span>
                    </div>
                </div>
            </div>
            <div class="metric-card border-slate">
                <div class="metric-ico s">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="label">Recettes en attente</div>
                    <div class="value metric-value--split">
                        <span class="metric-num">17</span>
                        <span class="metric-suffix metric-suffix--warn">Modération requise</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="bo-global-dash-grid2">
            <section class="panel-card" aria-labelledby="chart-inscriptions">
                <div class="panel-head" style="margin-bottom:1rem">
                    <h3 class="serif" id="chart-inscriptions">Inscriptions utilisateurs</h3>
                </div>
                <div class="bo-global-dash-bars" role="img">
                    <div class="bo-global-dash-bar" style="height:45%"></div>
                    <div class="bo-global-dash-bar" style="height:62%"></div>
                    <div class="bo-global-dash-bar" style="height:55%"></div>
                    <div class="bo-global-dash-bar" style="height:88%"></div>
                    <div class="bo-global-dash-bar" style="height:72%"></div>
                    <div class="bo-global-dash-bar" style="height:95%"></div>
                    <div class="bo-global-dash-bar" style="height:68%"></div>
                </div>
                <div class="bo-global-dash-bar-labels">
                    <span>Sem 1</span><span>Sem 2</span><span>Sem 3</span><span>Sem 4</span><span>Sem 5</span><span>Sem 6</span><span>Sem 7</span>
                </div>
            </section>
            <section class="panel-card" aria-labelledby="usage-modules">
                <div class="panel-head" style="margin-bottom:1rem">
                    <h3 class="serif" id="usage-modules">Utilisation par module</h3>
                </div>
                <div class="bo-global-dash-mod"><span>User &amp; Profil</span><div class="bo-global-dash-track"><span style="width:96%"></span></div><strong>96%</strong></div>
                <div class="bo-global-dash-mod"><span>Food &amp; Nutrition</span><div class="bo-global-dash-track"><span style="width:81%"></span></div><strong>81%</strong></div>
                <div class="bo-global-dash-mod"><span>Meal Planning</span><div class="bo-global-dash-track"><span style="width:74%"></span></div><strong>74%</strong></div>
                <div class="bo-global-dash-mod"><span>Progress</span><div class="bo-global-dash-track"><span style="width:68%"></span></div><strong>68%</strong></div>
                <div class="bo-global-dash-mod"><span>Blog</span><div class="bo-global-dash-track"><span style="width:<?= min(100, count($allPosts) * 5) ?>%"></span></div><strong><?= count($allPosts) ?> posts</strong></div>
                <div class="bo-global-dash-mod"><span>Recipes</span><div class="bo-global-dash-track"><span style="width:41%"></span></div><strong>41%</strong></div>
            </section>
        </div>

        <div class="bo-global-dash-tables">
            <section class="panel-card">
                <div class="panel-head" style="margin-bottom:1rem">
                    <h3 class="serif">Derniers utilisateurs inscrits</h3>
                </div>
                <div class="bo-global-dash-table-wrap">
                    <table class="bo-global-dash-table">
                        <thead>
                            <tr><th>Utilisateur</th><th>Email</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach (array_slice($users, 0, 5) as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['nom']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><span class="bo-global-dash-pill bo-global-dash-pill--ok">Actif</span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="panel-card">
                <div class="panel-head" style="margin-bottom:1rem">
                    <h3 class="serif">Activité récente</h3>
                </div>
                <ul class="bo-global-dash-activity">
                    <li><?= count($allPosts) ?> publications actives sur le blog.</li>
                    <li><?= count($users) ?> utilisateurs enregistrés.</li>
                    <li>Modération : 17 recettes en attente de validation.</li>
                    <li>Export CSV « utilisateurs » disponible.</li>
                </ul>
            </section>
        </div>
    </div>
</div>

<?php elseif ($panel === 'blog'): ?>
<!-- ══════════ BLOG MANAGEMENT PANEL ══════════ -->
<div class="blog-panel">

    <?php if ($flash): ?>
    <div class="bp-flash <?= $flash['type'] ?>"><?= $flash['text'] ?></div>
    <?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;flex-wrap:wrap;gap:0.8rem;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:800;color:#1a3228;">📝 Gestion du Blog</h1>
            <p style="color:#5c6b62;font-size:0.85rem;margin-top:0.2rem;">
                Modérez les publications et commentaires. Envoyez des avertissements aux utilisateurs.
            </p>
        </div>
        <a href="<?= $base ?>&panel=dashboard"
           style="padding:0.5rem 1.1rem;border-radius:2rem;background:#f4f7f5;border:1.5px solid #dce5df;font-size:0.85rem;font-weight:700;text-decoration:none;color:#1a3228;">
            ← Retour tableau de bord
        </a>
    </div>

    <!-- stats -->
    <div class="bp-stats">
        <div class="bp-stat">
            <div class="bp-stat-label">PUBLICATIONS</div>
            <div class="bp-stat-value"><?= count($allPosts) ?></div>
            <div class="bp-stat-sub">au total</div>
        </div>
        <div class="bp-stat">
            <div class="bp-stat-label">COMMENTAIRES</div>
            <div class="bp-stat-value"><?= count($allComments) ?></div>
            <div class="bp-stat-sub">au total</div>
        </div>
        <div class="bp-stat">
            <div class="bp-stat-label">UTILISATEURS</div>
            <div class="bp-stat-value"><?= count($users) ?></div>
            <div class="bp-stat-sub">pouvant être avertis</div>
        </div>
    </div>

    <!-- WARN BOX -->
    <div class="warn-box">
        <h4>⚠️ Envoyer un avertissement à un utilisateur</h4>
        <form method="POST" action="<?= $base ?>&panel=blog">
            <select name="target_user_id" required>
                <option value="">— Choisir un utilisateur —</option>
                <?php foreach ($users as $u): ?>
                <option value="<?= $u['id_utilisateur'] ?>">
                    <?= htmlspecialchars($u['nom']) ?> (<?= htmlspecialchars($u['email']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="warn_message" placeholder="Message d'avertissement..." required maxlength="300">
            <button type="submit" name="warn_user">⚠️ Envoyer</button>
        </form>
    </div>

    <!-- POSTS TABLE -->
    <div class="bp-section-title">
        📄 Publications <span class="bp-count"><?= count($allPosts) ?></span>
    </div>
    <div class="bp-search">
        <input type="text" id="postSearch" placeholder="🔍 Rechercher par titre ou auteur...">
    </div>

    <?php if (empty($allPosts)): ?>
    <div class="bp-empty"><span>📭</span>Aucune publication pour le moment.</div>
    <?php else: ?>
    <div class="bp-table-wrap">
        <table class="bp-table" id="postsTable">
            <thead>
                <tr>
                    <th>#</th><th>Titre</th><th>Auteur</th><th>Contenu</th><th>Image</th><th>Date</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allPosts as $p): ?>
            <tr data-search="<?= htmlspecialchars(strtolower($p['titre'] . ' ' . $p['nom'])) ?>">
                <td><span class="bp-pill bp-pill-gray"><?= $p['id_publication'] ?></span></td>
                <td class="cell-title"><?= htmlspecialchars($p['titre']) ?></td>
                <td><span class="bp-pill bp-pill-green"><?= htmlspecialchars($p['nom']) ?></span></td>
                <td class="cell-content"><?= htmlspecialchars($p['contenu']) ?></td>
                <td>
                    <?php if (!empty($p['image'])): ?>
                        <img src="/ProjetNutrismart/public/uploads/<?= $p['image'] ?>"
                             style="width:48px;height:48px;object-fit:cover;border-radius:0.5rem;">
                    <?php else: ?>
                        <span style="color:#ccc;font-size:0.8rem;">—</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap;color:#5c6b62;font-size:0.8rem;"><?= $p['date_publication'] ?></td>
                <td>
                    <div class="bp-action-btns">
                        <a class="bp-btn-delete"
                           href="<?= $base ?>&panel=blog&delete_post=<?= $p['id_publication'] ?>"
                           onclick="return confirm('Supprimer définitivement cette publication et tous ses commentaires ?')">
                            🗑️ Supprimer
                        </a>
                        <button class="bp-btn-warn"
                                onclick="openWarnModal(<?= $p['id_utilisateur'] ?>, '<?= htmlspecialchars(addslashes($p['nom'])) ?>')">
                            ⚠️ Avertir
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- COMMENTS TABLE -->
    <div class="bp-section-title" style="margin-top:2.5rem;">
        💬 Commentaires <span class="bp-count"><?= count($allComments) ?></span>
    </div>
    <div class="bp-search">
        <input type="text" id="commentSearch" placeholder="🔍 Rechercher par contenu ou auteur...">
    </div>

    <?php if (empty($allComments)): ?>
    <div class="bp-empty"><span>💬</span>Aucun commentaire pour le moment.</div>
    <?php else: ?>
    <div class="bp-table-wrap">
        <table class="bp-table" id="commentsTable">
            <thead>
                <tr>
                    <th>#</th><th>Auteur</th><th>Commentaire</th><th>Sur la publication</th><th>Date</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allComments as $c): ?>
            <tr data-search="<?= htmlspecialchars(strtolower($c['contenu'] . ' ' . $c['nom'])) ?>">
                <td><span class="bp-pill bp-pill-gray"><?= $c['id_commentaire'] ?></span></td>
                <td><span class="bp-pill bp-pill-blue"><?= htmlspecialchars($c['nom']) ?></span></td>
                <td class="cell-content"><?= htmlspecialchars($c['contenu']) ?></td>
                <td class="cell-title" style="max-width:180px;"><?= htmlspecialchars($c['post_titre']) ?></td>
                <td style="white-space:nowrap;color:#5c6b62;font-size:0.8rem;"><?= $c['date_commentaire'] ?></td>
                <td>
                    <div class="bp-action-btns">
                        <a class="bp-btn-delete"
                           href="<?= $base ?>&panel=blog&delete_comment=<?= $c['id_commentaire'] ?>"
                           onclick="return confirm('Supprimer ce commentaire ?')">
                            🗑️ Supprimer
                        </a>
                        <button class="bp-btn-warn"
                                onclick="openWarnModal(<?= $c['id_utilisateur'] ?>, '<?= htmlspecialchars(addslashes($c['nom'])) ?>')">
                            ⚠️ Avertir
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>

</main>

<!-- WARN MODAL -->
<div class="warn-modal-overlay" id="warnModalOverlay">
    <div class="warn-modal">
        <h3>⚠️ Avertir <span id="warnTargetName"></span></h3>
        <form method="POST" action="<?= $base ?>&panel=blog">
            <input type="hidden" name="target_user_id" id="warnTargetId">
            <label>Message d'avertissement</label>
            <textarea name="warn_message" placeholder="Expliquez la raison de l'avertissement..." required maxlength="300"></textarea>
            <div class="warn-modal-btns">
                <button type="button" class="cancel" onclick="closeWarnModal()">Annuler</button>
                <button type="submit" name="warn_user" class="send">⚠️ Envoyer l'avertissement</button>
            </div>
        </form>
    </div>
</div>

<script>
function openWarnModal(userId, userName) {
    document.getElementById('warnTargetId').value = userId;
    document.getElementById('warnTargetName').textContent = userName;
    document.getElementById('warnModalOverlay').classList.add('open');
}
function closeWarnModal() {
    document.getElementById('warnModalOverlay').classList.remove('open');
}
document.getElementById('warnModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeWarnModal();
});

const postSearch = document.getElementById('postSearch');
if (postSearch) {
    postSearch.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#postsTable tbody tr').forEach(row => {
            row.style.display = row.dataset.search.includes(q) ? '' : 'none';
        });
    });
}

const commentSearch = document.getElementById('commentSearch');
if (commentSearch) {
    commentSearch.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#commentsTable tbody tr').forEach(row => {
            row.style.display = row.dataset.search.includes(q) ? '' : 'none';
        });
    });
}
</script>
</body>
</html>