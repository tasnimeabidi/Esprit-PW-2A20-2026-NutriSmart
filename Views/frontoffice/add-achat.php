<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../Services/AchatService.php';
    require_once __DIR__ . '/../../Services/BudgetService.php';
    require_once __DIR__ . '/../../Services/AlimentService.php';

    $achatService = new AchatService();
    $budgetService = new BudgetService();
    $alimentService = new AlimentService();

    $aliment_id = isset($_POST['aliment_id']) ? (int) $_POST['aliment_id'] : 0;
    $prix_unitaire = isset($_POST['prix_unitaire']) ? floatval($_POST['prix_unitaire']) : 0;
    $quantite = isset($_POST['quantite']) ? floatval($_POST['quantite']) : 0;

    if ($aliment_id <= 0 || $prix_unitaire <= 0 || $quantite <= 0) {
        header('Location: user-achat.php?error=' . urlencode('Données invalides'));
        exit();
    }

    $prix_total = $prix_unitaire * $quantite;

    $budget = $budgetService->getBudgetByUserId($user_id);
    $totalDepenses = $achatService->getTotalDepensesByUserId($user_id);
    $reste = $budget ? ((float) $budget['montant'] - $totalDepenses) : 0;

    if ($reste <= 0) {
        header('Location: user-achat.php?error=' . urlencode('Budget insuffisant pour effectuer cet achat'));
        exit();
    }

    if ($prix_total > $reste) {
        header('Location: user-achat.php?error=' . urlencode(
            'Le montant (' . number_format($prix_total, 2) . ' TND) dépasse le reste (' . number_format($reste, 2) . ' TND)'
        ));
        exit();
    }

    $aliment = $alimentService->getAlimentById($aliment_id);
    if (!$aliment) {
        header('Location: user-achat.php?error=' . urlencode('Aliment non trouvé'));
        exit();
    }

    if ($achatService->addPurchase($user_id, $aliment_id, $quantite, $prix_total)) {
        header('Location: user-achat.php?success=1');
        exit();
    }

    header('Location: user-achat.php?error=' . urlencode("Erreur lors de l'enregistrement de l'achat"));
    exit();
}

header('Location: user-achat.php');
exit();
