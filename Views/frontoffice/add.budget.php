<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?next=' . rawurlencode('budget-user.php'));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['budget'])) {
    require_once __DIR__ . '/../../Services/BudgetService.php';

    $budgetService = new BudgetService();
    $raw = preg_replace('/\s+/u', '', trim((string) $_POST['budget']));
    $raw = str_replace(',', '.', $raw);
    $budget = (float) $raw;

    if ($budget > 0) {
        if ($budgetService->setBudget($user_id, $budget)) {
            header('Location: budget-user.php?success=1');
            exit();
        }
        $error = "Erreur lors de l'enregistrement du budget";
    } else {
        $error = 'Veuillez saisir un budget valide';
    }
}

header('Location: budget-user.php' . (isset($error) ? '?error=' . urlencode($error) : ''));
exit();
