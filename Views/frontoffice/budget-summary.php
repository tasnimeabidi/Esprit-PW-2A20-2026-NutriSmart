<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?next=' . rawurlencode('budget-summary.php'));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

require_once __DIR__ . '/../../Services/BudgetService.php';
require_once __DIR__ . '/../../Services/AchatService.php';
require_once __DIR__ . '/../../Services/UserService.php';

$budgetService = new BudgetService();
$achatService = new AchatService();
$userService = new UserService();

// Get user info
$user = $userService->getUserById($user_id);
if (!$user) {
    die('Utilisateur non trouvé');
}

// Get user's budget
$budget = $budgetService->getBudgetByUserId($user_id);
$totalDepenses = $achatService->getTotalDepensesByUserId($user_id);
$achats = $achatService->getAchatsByUserId($user_id);

// Calculate remaining budget
$reste = $budget ? ((float) $budget['montant'] - $totalDepenses) : 0;
$bMontant = $budget ? (float) $budget['montant'] : 0;
$pourcentage = ($budget && $bMontant > 0) ? min(100.0, ($totalDepenses / $bMontant) * 100.0) : 0.0;

// Get last 5 purchases
$derniersAchats = array_slice($achats, 0, 5);

$__scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$__basePath = ($__scriptDir === '/' || $__scriptDir === '\\' || $__scriptDir === '.')
    ? ''
    : rtrim(str_replace('\\', '/', $__scriptDir), '/');
$__p = $__basePath === '' ? '' : $__basePath . '/';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résumé Budget - <?php echo htmlspecialchars($user['nom']); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($__p); ?>css/mp-dashboard.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($__p); ?>css/shared-styles.css?v=3">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($__p); ?>css/nutrismart-budget-youssef.css?v=1">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($__p); ?>css/budget-summary-layout.css?v=1">
</head>
<body data-site-nav="" class="budget-summary-page">
<div id="site-nav-root"></div>
<script src="<?php echo htmlspecialchars($__p); ?>js/site-nav-loader.js"></script>

<div class="app app--fo-topnav">

<main class="main">
<div class="budget-summary-inner">
    <div class="summary-card">
        <div class="header">
            <h1>💰 Résumé Budget</h1>
            <p><?php echo htmlspecialchars($user['nom']); ?></p>
        </div>

        <div class="stat-grid">
            <div class="stat-item total">
                <h3>Budget Total</h3>
                <div class="value"><?php echo $budget ? number_format($budget['montant'], 2) : '0.00'; ?> TND</div>
            </div>
            <div class="stat-item">
                <h3>Dépenses</h3>
                <div class="value"><?php echo number_format($totalDepenses, 2); ?> TND</div>
            </div>
            <div class="stat-item remaining">
                <h3>Restant</h3>
                <div class="value"><?php echo number_format(max(0, $reste), 2); ?> TND</div>
            </div>
            <div class="stat-item">
                <h3>Progression</h3>
                <div class="value"><?php echo number_format($pourcentage, 1); ?>%</div>
            </div>
        </div>

        <div class="progress-section">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $pourcentage; ?>%"></div>
            </div>
            <div class="progress-text">
                <?php echo number_format($pourcentage, 1); ?>% du budget utilisé
            </div>
        </div>

        <div class="purchases-section">
            <h3>📋 Derniers achats</h3>
            <?php if (empty($derniersAchats)): ?>
                <div class="no-data">Aucun achat enregistré</div>
            <?php else: ?>
                <?php foreach ($derniersAchats as $achat): ?>
                    <div class="purchase-item">
                        <div class="purchase-name"><?php echo htmlspecialchars($achat['aliment_nom']); ?></div>
                        <div class="purchase-details">
                            <div class="purchase-price"><?php echo number_format($achat['prix_total'], 2); ?> TND</div>
                            <div class="purchase-date"><?php echo date('d/m/Y', strtotime($achat['date_achat'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>Généré par NutriSmart • <?php echo date('d/m/Y H:i'); ?></p>
            <p style="margin-top:12px;"><a href="budget-user.php" class="btn">Retour Budget &amp; courses</a></p>
        </div>
    </div>
</div>
</main>
</div>
</body>
</html>