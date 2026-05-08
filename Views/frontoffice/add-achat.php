<?php
// Check if user ID is provided in URL
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    die('Erreur: ID utilisateur manquant');
}

$user_id = intval($_GET['user_id']);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../../Services/AchatService.php';
    require_once '../../Services/BudgetService.php';
    require_once '../../Services/AlimentService.php';
    
    $achatService = new AchatService();
    $budgetService = new BudgetService();
    $alimentService = new AlimentService();
    
    // Get form data
    $aliment_id = isset($_POST['aliment_id']) ? intval($_POST['aliment_id']) : 0;
    $prix_unitaire = isset($_POST['prix_unitaire']) ? floatval($_POST['prix_unitaire']) : 0;
    $quantite = isset($_POST['quantite']) ? intval($_POST['quantite']) : 0;
    
    // Validate input
    if ($aliment_id <= 0 || $prix_unitaire <= 0 || $quantite <= 0) {
        $error = "Données invalides";
        header("Location: user-achat.php?user_id={$user_id}&error=" . urlencode($error));
        exit();
    }
    
    // Calculate total
    $prix_total = $prix_unitaire * $quantite;
    
    // Check budget
    $budget = $budgetService->getBudgetByUserId($user_id);
    $totalDepenses = $achatService->getTotalDepensesByUserId($user_id);
    $reste = $budget ? ($budget['montant'] - $totalDepenses) : 0;
    
    if ($reste <= 0) {
        $error = "Budget insuffisant pour effectuer cet achat";
        header("Location: user-achat.php?user_id={$user_id}&error=" . urlencode($error));
        exit();
    }
    
    if ($prix_total > $reste) {
        $error = "Le montant de l'achat (" . number_format($prix_total, 2) . " TND) dépasse votre budget restant (" . number_format($reste, 2) . " TND)";
        header("Location: user-achat.php?user_id={$user_id}&error=" . urlencode($error));
        exit();
    }
    
    // Check if aliment exists
    $aliment = $alimentService->getAlimentById($aliment_id);
    if (!$aliment) {
        $error = "Aliment non trouvé";
        header("Location: user-achat.php?user_id={$user_id}&error=" . urlencode($error));
        exit();
    }
    
    // Add purchase
    if ($achatService->addPurchase($user_id, $aliment_id, $quantite, $prix_total)) {
        // Redirect back to shop with success message
        header("Location: user-achat.php?user_id={$user_id}&success=1");
        exit();
    } else {
        $error = "Erreur lors de l'enregistrement de l'achat";
        header("Location: user-achat.php?user_id={$user_id}&error=" . urlencode($error));
        exit();
    }
}

// If not a POST request, redirect back to shop
header("Location: user-achat.php?user_id={$user_id}");
exit();
?>