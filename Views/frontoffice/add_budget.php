<?php
// Check if user ID is provided in URL
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    die('Erreur: ID utilisateur manquant');
}

$user_id = intval($_GET['user_id']);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['budget'])) {
    require_once '../../Services/BudgetService.php';
    
    $budgetService = new BudgetService();
    $budget = floatval($_POST['budget']);
    
    if ($budget > 0) {
        if ($budgetService->setBudget($user_id, $budget)) {
            // Redirect back to budget page with success message
            header("Location: budget-user.php?user_id={$user_id}&success=1");
            exit();
        } else {
            $error = "Erreur lors de l'enregistrement du budget";
        }
    } else {
        $error = "Veuillez saisir un budget valide";
    }
}

// If not a POST request or there was an error, redirect back to budget page
header("Location: budget-user.php?user_id={$user_id}" . (isset($error) ? "&error=" . urlencode($error) : ""));
exit();
?>