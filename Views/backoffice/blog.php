<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function blogAdminIsLocalRequest(): bool
{
    $addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return $addr === '127.0.0.1'
        || $addr === '::1'
        || strncmp($addr, '127.', 4) === 0
        || strpos($addr, '::ffff:127.') === 0;
}

function blogAdminTableColumns(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
        $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        return array_values(array_map('strval', is_array($columns) ? $columns : []));
    } catch (Throwable $e) {
        return [];
    }
}

function blogAdminFirstExistingColumn(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }
    return null;
}

function blogAdminExcerpt(?string $text, int $length = 120): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return 'Sans contenu';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
    }
    return strlen($text) > $length ? substr($text, 0, $length - 1) . '...' : $text;
}

function blogAdminSetFlash(string $type, string $message): void
{
    $_SESSION['blog_admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
    header('Location: blog.php', true, 303);
    exit;
}

function blogAdminDefaultWarningMessage(string $entityType, string $subject): string
{
    if ($entityType === 'comment') {
        return 'Avertissement de l’administrateur : votre commentaire "' . $subject . '" ne respecte pas les règles de la plateforme.';
    }
    return 'Avertissement de l’administrateur : votre publication "' . $subject . '" ne respecte pas les règles de la plateforme.';
}

$authUser = null;
$adminName = 'Administrateur';
$adminInitials = 'NS';
$isAuthorized = false;

if (isset($_SESSION['user_id'])) {
    try {
        $authUserModel = new User();
        $authUser = $authUserModel->getById((int) $_SESSION['user_id']);
        $authRole = strtolower(trim((string) ($authUser['role'] ?? ($_SESSION['role'] ?? ''))));
        $isAuthorized = ($authRole === 'admin');
        $adminNameCandidate = trim((string) ($authUser['nom'] ?? ''));
        if ($adminNameCandidate !== '') {
            $adminName = $adminNameCandidate;
            $pieces = preg_split('/\s+/', $adminNameCandidate) ?: [];
            $initials = '';
            foreach ($pieces as $piece) {
                if ($piece !== '') {
                    $initials .= strtoupper(substr($piece, 0, 1));
                }
            }
            if ($initials !== '') {
                $adminInitials = substr($initials, 0, 2);
            }
        }
    } catch (Throwable $e) {
        $authUser = null;
    }
}

if (!$isAuthorized && !blogAdminIsLocalRequest()) {
    http_response_code(403);
    echo 'Accès non autorisé. Réservé aux administrateurs.';
    exit;
}

$pdo = Database::getConnection();
$publicationColumns = blogAdminTableColumns($pdo, 'publication');
$commentColumns = blogAdminTableColumns($pdo, 'commentaire');
$notificationColumns = blogAdminTableColumns($pdo, 'notification');

$postIdColumn = blogAdminFirstExistingColumn($publicationColumns, ['id_publication', 'id']);
$commentIdColumn = blogAdminFirstExistingColumn($commentColumns, ['id_commentaire', 'id']);
$commentPostColumn = blogAdminFirstExistingColumn($commentColumns, ['id_publication']);
$notificationUserColumn = blogAdminFirstExistingColumn($notificationColumns, ['id_utilisatuer', 'id_utilisateur']);

$canManagePosts = $postIdColumn !== null;
$canManageComments = $commentIdColumn !== null && $commentPostColumn !== null;
$canWarnUsers = $notificationUserColumn !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    try {
        if ($action === 'delete_post') {
            if (!$canManagePosts) {
                throw new RuntimeException('La table des publications est introuvable.');
            }

            $postId = (int) ($_POST['post_id'] ?? 0);
            if ($postId <= 0) {
                throw new RuntimeException('Publication invalide.');
            }

            $stmt = $pdo->prepare('DELETE FROM publication WHERE `' . $postIdColumn . '` = ? LIMIT 1');
            $stmt->execute([$postId]);

            if ($stmt->rowCount() < 1) {
                throw new RuntimeException('Publication introuvable.');
            }

            blogAdminSetFlash('success', 'Publication supprimée avec succès.');
        }

        if ($action === 'delete_comment') {
            if (!$canManageComments) {
                throw new RuntimeException('La table des commentaires est introuvable.');
            }

            $commentId = (int) ($_POST['comment_id'] ?? 0);
            if ($commentId <= 0) {
                throw new RuntimeException('Commentaire invalide.');
            }

            $stmt = $pdo->prepare('DELETE FROM commentaire WHERE `' . $commentIdColumn . '` = ? LIMIT 1');
            $stmt->execute([$commentId]);

            if ($stmt->rowCount() < 1) {
                throw new RuntimeException('Commentaire introuvable.');
            }

            blogAdminSetFlash('success', 'Commentaire supprimé avec succès.');
        }

        if ($action === 'warn_post') {
            if (!$canManagePosts || !$canWarnUsers) {
                throw new RuntimeException('Les notifications du blog ne sont pas disponibles.');
            }

            $postId = (int) ($_POST['post_id'] ?? 0);
            if ($postId <= 0) {
                throw new RuntimeException('Publication invalide.');
            }

            $stmt = $pdo->prepare(
                'SELECT id_utilisateur, COALESCE(NULLIF(TRIM(titre), \'\'), \'Publication sans titre\') AS titre
                 FROM publication
                 WHERE `' . $postIdColumn . '` = ?
                 LIMIT 1'
            );
            $stmt->execute([$postId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                throw new RuntimeException('Publication introuvable.');
            }

            $customMessage = trim((string) ($_POST['warning_message'] ?? ''));
            $message = $customMessage !== ''
                ? 'Avertissement de l’administrateur : ' . $customMessage
                : blogAdminDefaultWarningMessage('post', (string) $post['titre']);

            $insert = $pdo->prepare(
                'INSERT INTO notification (`' . $notificationUserColumn . '`, message, is_read)
                 VALUES (?, ?, 0)'
            );
            $insert->execute([(int) $post['id_utilisateur'], $message]);

            blogAdminSetFlash('success', 'Avertissement envoyé à l’auteur de la publication.');
        }

        if ($action === 'warn_comment') {
            if (!$canManageComments || !$canWarnUsers) {
                throw new RuntimeException('Les notifications du blog ne sont pas disponibles.');
            }

            $commentId = (int) ($_POST['comment_id'] ?? 0);
            if ($commentId <= 0) {
                throw new RuntimeException('Commentaire invalide.');
            }

            $stmt = $pdo->prepare(
                'SELECT c.id_utilisateur, COALESCE(NULLIF(TRIM(c.contenu), \'\'), \'Commentaire sans contenu\') AS contenu
                 FROM commentaire c
                 WHERE c.`' . $commentIdColumn . '` = ?
                 LIMIT 1'
            );
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment) {
                throw new RuntimeException('Commentaire introuvable.');
            }

            $customMessage = trim((string) ($_POST['warning_message'] ?? ''));
            $message = $customMessage !== ''
                ? 'Avertissement de l’administrateur : ' . $customMessage
                : blogAdminDefaultWarningMessage('comment', blogAdminExcerpt((string) $comment['contenu'], 80));

            $insert = $pdo->prepare(
                'INSERT INTO notification (`' . $notificationUserColumn . '`, message, is_read)
                 VALUES (?, ?, 0)'
            );
            $insert->execute([(int) $comment['id_utilisateur'], $message]);

            blogAdminSetFlash('success', 'Avertissement envoyé à l’auteur du commentaire.');
        }

        if ($action !== '') {
            throw new RuntimeException('Action non reconnue.');
        }
    } catch (Throwable $e) {
        blogAdminSetFlash('error', 'Action impossible : ' . $e->getMessage());
    }
}

$flash = $_SESSION['blog_admin_flash'] ?? null;
unset($_SESSION['blog_admin_flash']);

$posts = [];
$comments = [];
$recentNotifications = [];
$metrics = [
    'posts' => 0,
    'comments' => 0,
    'notifications' => 0,
    'authors' => 0,
];
$loadError = null;

try {
    if (!$canManagePosts) {
        throw new RuntimeException('Le schéma blog n’a pas été trouvé dans la base.');
    }

    $metrics['posts'] = (int) $pdo->query('SELECT COUNT(*) FROM publication')->fetchColumn();
    $metrics['authors'] = (int) $pdo->query('SELECT COUNT(DISTINCT id_utilisateur) FROM publication')->fetchColumn();

    if ($canManageComments) {
        $metrics['comments'] = (int) $pdo->query('SELECT COUNT(*) FROM commentaire')->fetchColumn();
    }

    if ($canWarnUsers) {
        $metrics['notifications'] = (int) $pdo->query('SELECT COUNT(*) FROM notification')->fetchColumn();
    }

    $postsStmt = $pdo->query(
        'SELECT
            p.`' . $postIdColumn . '` AS post_id,
            p.id_utilisateur AS user_id,
            COALESCE(NULLIF(TRIM(p.titre), \'\'), \'Sans titre\') AS titre,
            p.contenu,
            p.date_publication,
            COALESCE(NULLIF(TRIM(u.nom), \'\'), CONCAT(\'Utilisateur #\', p.id_utilisateur)) AS auteur,
            ' . ($canManageComments ? '(SELECT COUNT(*) FROM commentaire c WHERE c.`' . $commentPostColumn . '` = p.`' . $postIdColumn . '`)': '0') . ' AS comments_count
         FROM publication p
         LEFT JOIN utilisateur u ON u.id_utilisateur = p.id_utilisateur
         ORDER BY p.date_publication DESC
         LIMIT 12'
    );
    $posts = $postsStmt ? $postsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    if ($canManageComments) {
        $commentsStmt = $pdo->query(
            'SELECT
                c.`' . $commentIdColumn . '` AS comment_id,
                c.id_utilisateur AS user_id,
                c.contenu,
                c.date_commentaire,
                COALESCE(NULLIF(TRIM(u.nom), \'\'), CONCAT(\'Utilisateur #\', c.id_utilisateur)) AS auteur,
                COALESCE(NULLIF(TRIM(p.titre), \'\'), \'Publication supprimée ou sans titre\') AS publication_titre
             FROM commentaire c
             LEFT JOIN utilisateur u ON u.id_utilisateur = c.id_utilisateur
             LEFT JOIN publication p ON p.`' . $postIdColumn . '` = c.`' . $commentPostColumn . '`
             ORDER BY c.date_commentaire DESC
             LIMIT 12'
        );
        $comments = $commentsStmt ? $commentsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    if ($canWarnUsers) {
        $notificationsStmt = $pdo->query(
            'SELECT
                n.message,
                n.is_read,
                n.date_notification,
                COALESCE(NULLIF(TRIM(u.nom), \'\'), CONCAT(\'Utilisateur #\', n.`' . $notificationUserColumn . '`)) AS utilisateur
             FROM notification n
             LEFT JOIN utilisateur u ON u.id_utilisateur = n.`' . $notificationUserColumn . '`
             ORDER BY n.date_notification DESC
             LIMIT 8'
        );
        $recentNotifications = $notificationsStmt ? $notificationsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
} catch (Throwable $e) {
    $loadError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>NutriSmart — Blog</title>
  <link rel="stylesheet" href="../frontoffice/css/mp-dashboard.css" />
  <link rel="stylesheet" href="backoffice-shell.css" />
  <style>
    .bo-blog-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1rem;
      flex-wrap: wrap;
      margin-bottom: 2rem;
    }

    .bo-blog-subtitle {
      color: var(--bo-muted);
      margin-top: 0.35rem;
      font-size: 0.9rem;
    }

    .bo-blog-grid {
      display: grid;
      grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
      gap: 1rem;
      align-items: start;
    }

    .bo-blog-stack {
      display: grid;
      gap: 1rem;
    }

    .bo-blog-card {
      background: var(--bo-card);
      border: 1px solid var(--bo-border);
      border-radius: 18px;
      padding: 1.25rem;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
    }

    .bo-blog-card h2 {
      margin: 0 0 1rem;
      font-size: 1.05rem;
    }

    .bo-blog-table {
      width: 100%;
      border-collapse: collapse;
    }

    .bo-blog-table th,
    .bo-blog-table td {
      padding: 0.85rem 0.5rem;
      text-align: left;
      vertical-align: top;
      border-bottom: 1px solid var(--bo-border);
    }

    .bo-blog-table th {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--bo-muted);
    }

    .bo-blog-table tr:last-child td {
      border-bottom: none;
    }

    .bo-blog-meta {
      display: block;
      margin-top: 0.35rem;
      font-size: 0.8rem;
      color: var(--bo-muted);
    }

    .bo-blog-excerpt {
      display: block;
      margin-top: 0.45rem;
      color: var(--bo-muted);
      font-size: 0.88rem;
      line-height: 1.45;
    }

    .bo-blog-actions {
      display: grid;
      gap: 0.55rem;
      min-width: 220px;
    }

    .bo-action-form {
      display: grid;
      gap: 0.45rem;
    }

    .bo-action-input {
      width: 100%;
      min-width: 0;
      padding: 0.65rem 0.75rem;
      border-radius: 10px;
      border: 1px solid var(--bo-border);
      background: #131922;
      color: var(--bo-text);
    }

    .bo-action-row {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .bo-btn {
      border: 0;
      border-radius: 10px;
      padding: 0.65rem 0.85rem;
      font-weight: 700;
      cursor: pointer;
      font-family: inherit;
    }

    .bo-btn-warn {
      background: rgba(249, 168, 37, 0.16);
      color: #ffcc80;
      border: 1px solid rgba(249, 168, 37, 0.3);
    }

    .bo-btn-delete {
      background: rgba(229, 57, 53, 0.16);
      color: #ff8a80;
      border: 1px solid rgba(229, 57, 53, 0.3);
    }

    .bo-flash {
      padding: 0.95rem 1rem;
      border-radius: 12px;
      margin-bottom: 1rem;
      border: 1px solid transparent;
    }

    .bo-flash-success {
      background: rgba(61, 186, 82, 0.12);
      border-color: rgba(61, 186, 82, 0.3);
      color: var(--bo-text);
    }

    .bo-flash-error {
      background: rgba(229, 57, 53, 0.12);
      border-color: rgba(229, 57, 53, 0.3);
      color: var(--bo-text);
    }

    .bo-empty {
      margin: 0;
      color: var(--bo-muted);
    }

    .bo-blog-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: grid;
      gap: 0.85rem;
    }

    .bo-blog-list li {
      border: 1px solid var(--bo-border);
      background: #131922;
      border-radius: 14px;
      padding: 0.9rem 1rem;
    }

    .bo-blog-list strong {
      display: block;
      margin-bottom: 0.25rem;
    }

    .bo-blog-list span {
      color: var(--bo-muted);
      font-size: 0.88rem;
      line-height: 1.45;
    }

    .bo-helper {
      color: var(--bo-muted);
      font-size: 0.86rem;
      line-height: 1.5;
      margin: 0;
    }

    @media (max-width: 1180px) {
      .bo-blog-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 720px) {
      .bo-blog-actions {
        min-width: 0;
      }
    }
  </style>
</head>
<body class="bo-shell-body">
  <header class="topbar">
    <a href="nutrismart-dashboard.html" class="topbar-logo">
      <svg width="32" height="32" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible">
        <mask id="biteMaskBlog">
          <rect x="-20" y="-20" width="140" height="140" fill="white" />
          <circle cx="92" cy="35" r="18" fill="black" />
          <circle cx="84" cy="62" r="14" fill="black" />
        </mask>
        <g mask="url(#biteMaskBlog)">
          <path d="M 20 80 C 35 45 65 25 90 10 C 90 60 70 90 20 80 Z" fill="#3dba52" />
          <path d="M 20 80 C 10 30 40 10 90 10 C 65 25 35 45 20 80 Z" fill="#8bc34a" />
        </g>
        <path d="M 22 78 L 12 92" stroke="#3dba52" stroke-width="7" stroke-linecap="round" />
      </svg>
      <div style="display:flex;align-items:center;gap:2px">
        <span style="color:#3dba52">Nutri</span><span style="color:#8bc34a">Smart</span>
      </div>
      <span class="logo-badge">ADMIN</span>
    </a>
    <div class="topbar-right">
      <div class="notif-btn" title="Notifications blog">🔔<?php if ($metrics['notifications'] > 0): ?><div class="notif-dot"></div><?php endif; ?></div>
      <div class="admin-avatar">
        <div class="avatar-img" style="display:grid;place-items:center;background:#1f2a35;font-weight:800;color:#d9e3ee;"><?= h($adminInitials) ?></div>
        <div class="admin-info">
          <div class="admin-name"><?= h($adminName) ?></div>
          <div class="admin-role">Administration blog</div>
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
    </a>
    <a class="nav-item" href="aliment.php">
      <span class="nav-icon">🥗</span> Aliment-Recette
    </a>
    <a class="nav-item" href="recettes.php">
      <span class="nav-icon">📖</span> Recettes
    </a>
    <a class="nav-item active" href="blog.php">
      <span class="nav-icon">📝</span> Blog
    </a>
    <a class="nav-item" href="crud-plan-repas-sport.html">
      <span class="nav-icon">📅</span> Plans de repas
    </a>
    <a class="nav-item" href="../../nutrismart-dashboard.php?view=view-suivi">
      <span class="nav-icon">📉</span> Statistiques
    </a>
    <a class="nav-item" href="../frontoffice/budget-admin.php">
      <span class="nav-icon">🛒</span> Courses &amp; Budget
    </a>

    <div class="nav-section-label">Système</div>
    <a class="nav-item" href="../frontoffice/nutrismart-website.html" style="color:#3dba52">
      <span class="nav-icon">🌐</span> Voir le site
    </a>
  </aside>

  <main class="bo-shell-main">
    <div class="app app--embed-dash">
      <div class="main dash-workspace bo-global-dash">
        <header class="bo-blog-head">
          <div>
            <h1 class="serif">Blog</h1>
            <p class="bo-blog-subtitle">Gestion admin des publications, commentaires et avertissements utilisateurs.</p>
          </div>
          <div class="bo-global-dash-actions">
            <a class="btn-sm btn-ghost" href="nutrismart-dashboard.html">Retour au dashboard</a>
          </div>
        </header>

        <?php if (is_array($flash) && isset($flash['type'], $flash['message'])): ?>
          <div class="bo-flash <?= $flash['type'] === 'error' ? 'bo-flash-error' : 'bo-flash-success' ?>">
            <?= h($flash['message']) ?>
          </div>
        <?php endif; ?>

        <?php if ($loadError !== null): ?>
          <div class="bo-flash bo-flash-error">
            <?= h('Erreur blog : ' . $loadError) ?>
          </div>
        <?php endif; ?>

        <section class="metrics-row" aria-label="Indicateurs blog">
          <div class="metric-card border-forest">
            <div>
              <div class="label">Publications</div>
              <div class="value"><?= h((string) $metrics['posts']) ?></div>
            </div>
          </div>
          <div class="metric-card border-mint">
            <div>
              <div class="label">Commentaires</div>
              <div class="value"><?= h((string) $metrics['comments']) ?></div>
            </div>
          </div>
          <div class="metric-card border-orange">
            <div>
              <div class="label">Notifications</div>
              <div class="value"><?= h((string) $metrics['notifications']) ?></div>
            </div>
          </div>
          <div class="metric-card border-slate">
            <div>
              <div class="label">Auteurs actifs</div>
              <div class="value"><?= h((string) $metrics['authors']) ?></div>
            </div>
          </div>
        </section>

        <div class="bo-blog-grid">
          <div class="bo-blog-stack">
            <section class="bo-blog-card" id="articles">
              <h2 class="serif">Publications récentes</h2>
              <?php if ($posts === []): ?>
                <p class="bo-empty">Aucune publication trouvée.</p>
              <?php else: ?>
                <table class="bo-blog-table">
                  <thead>
                    <tr>
                      <th>Publication</th>
                      <th>Auteur</th>
                      <th>Commentaires</th>
                      <th>Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($posts as $post): ?>
                      <tr>
                        <td>
                          <strong><?= h($post['titre'] ?? 'Sans titre') ?></strong>
                          <span class="bo-blog-excerpt"><?= h(blogAdminExcerpt((string) ($post['contenu'] ?? ''))) ?></span>
                        </td>
                        <td><?= h($post['auteur'] ?? 'Utilisateur') ?></td>
                        <td><?= h((string) ($post['comments_count'] ?? '0')) ?></td>
                        <td>
                          <?= h((string) ($post['date_publication'] ?? '')) ?>
                          <span class="bo-blog-meta">ID #<?= h((string) ($post['post_id'] ?? '')) ?></span>
                        </td>
                        <td>
                          <div class="bo-blog-actions">
                            <?php if ($canWarnUsers): ?>
                              <form method="post" class="bo-action-form">
                                <input type="hidden" name="action" value="warn_post" />
                                <input type="hidden" name="post_id" value="<?= h((string) ($post['post_id'] ?? '0')) ?>" />
                                <input class="bo-action-input" type="text" name="warning_message" maxlength="255" placeholder="Message d’avertissement pour l’auteur" />
                                <div class="bo-action-row">
                                  <button class="bo-btn bo-btn-warn" type="submit">Avertir l’auteur</button>
                                </div>
                              </form>
                            <?php endif; ?>
                            <form method="post" class="bo-action-form" onsubmit="return confirm('Supprimer cette publication ?');">
                              <input type="hidden" name="action" value="delete_post" />
                              <input type="hidden" name="post_id" value="<?= h((string) ($post['post_id'] ?? '0')) ?>" />
                              <div class="bo-action-row">
                                <button class="bo-btn bo-btn-delete" type="submit">Supprimer la publication</button>
                              </div>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </section>

            <section class="bo-blog-card">
              <h2 class="serif">Commentaires récents</h2>
              <?php if (!$canManageComments): ?>
                <p class="bo-empty">Le schéma des commentaires n’est pas disponible dans cette base.</p>
              <?php elseif ($comments === []): ?>
                <p class="bo-empty">Aucun commentaire trouvé.</p>
              <?php else: ?>
                <table class="bo-blog-table">
                  <thead>
                    <tr>
                      <th>Commentaire</th>
                      <th>Auteur</th>
                      <th>Publication</th>
                      <th>Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($comments as $comment): ?>
                      <tr>
                        <td><?= h(blogAdminExcerpt((string) ($comment['contenu'] ?? ''), 150)) ?></td>
                        <td><?= h($comment['auteur'] ?? 'Utilisateur') ?></td>
                        <td><?= h($comment['publication_titre'] ?? 'Publication') ?></td>
                        <td>
                          <?= h((string) ($comment['date_commentaire'] ?? '')) ?>
                          <span class="bo-blog-meta">ID #<?= h((string) ($comment['comment_id'] ?? '')) ?></span>
                        </td>
                        <td>
                          <div class="bo-blog-actions">
                            <?php if ($canWarnUsers): ?>
                              <form method="post" class="bo-action-form">
                                <input type="hidden" name="action" value="warn_comment" />
                                <input type="hidden" name="comment_id" value="<?= h((string) ($comment['comment_id'] ?? '0')) ?>" />
                                <input class="bo-action-input" type="text" name="warning_message" maxlength="255" placeholder="Message d’avertissement pour l’auteur du commentaire" />
                                <div class="bo-action-row">
                                  <button class="bo-btn bo-btn-warn" type="submit">Avertir l’auteur</button>
                                </div>
                              </form>
                            <?php endif; ?>
                            <form method="post" class="bo-action-form" onsubmit="return confirm('Supprimer ce commentaire ?');">
                              <input type="hidden" name="action" value="delete_comment" />
                              <input type="hidden" name="comment_id" value="<?= h((string) ($comment['comment_id'] ?? '0')) ?>" />
                              <div class="bo-action-row">
                                <button class="bo-btn bo-btn-delete" type="submit">Supprimer le commentaire</button>
                              </div>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </section>
          </div>

          <div class="bo-blog-stack">
            <section class="bo-blog-card">
              <h2 class="serif">Notifications récentes</h2>
              <?php if (!$canWarnUsers): ?>
                <p class="bo-empty">La table `notification` n’est pas disponible. Les avertissements ne peuvent pas être envoyés.</p>
              <?php elseif ($recentNotifications === []): ?>
                <p class="bo-empty">Aucune notification blog pour le moment.</p>
              <?php else: ?>
                <ul class="bo-blog-list">
                  <?php foreach ($recentNotifications as $notification): ?>
                    <li>
                      <strong><?= h($notification['utilisateur'] ?? 'Utilisateur') ?></strong>
                      <span><?= h($notification['message'] ?? '') ?></span>
                      <span class="bo-blog-meta"><?= h((string) ($notification['date_notification'] ?? '')) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </section>

            <section class="bo-blog-card">
              <h2 class="serif">Règles admin</h2>
              <ul class="bo-blog-list">
                <li>
                  <strong>Avertir un utilisateur</strong>
                  <span>Le message est enregistré dans la table `notification` du blog pour qu’il soit visible côté utilisateur.</span>
                </li>
                <li>
                  <strong>Supprimer une publication</strong>
                  <span>La suppression retire le post du blog. Les commentaires liés disparaissent aussi si la contrainte SQL est active.</span>
                </li>
                <li>
                  <strong>Supprimer un commentaire</strong>
                  <span>Action immédiate, utile pour la modération rapide sans modifier le reste du module blog.</span>
                </li>
              </ul>
              <p class="bo-helper">Si tu laisses le champ d’avertissement vide, un message admin standard est envoyé automatiquement.</p>
            </section>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
