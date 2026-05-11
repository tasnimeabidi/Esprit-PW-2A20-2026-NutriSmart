<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/controllers/AlimentController.php';
require_once dirname(__DIR__, 2) . '/controllers/RecetteController.php';

$controller = new AlimentController();
$recetteController = new RecetteController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_aliment') {
            $controller->createAliment($_POST);
        } elseif ($_POST['action'] === 'delete_aliment') {
            $controller->deleteAliment($_POST['id']);
        } elseif ($_POST['action'] === 'update_aliment') {
            $controller->updateAliment($_POST);
        } elseif ($_POST['action'] === 'add_recette') {
            $recetteController->createRecette($_POST, 'approved');
        } elseif ($_POST['action'] === 'delete_recette') {
            $recetteController->deleteRecette($_POST['id']);
        } elseif ($_POST['action'] === 'update_recette') {
            $recetteController->updateRecette($_POST);
        } elseif ($_POST['action'] === 'approve_recette') {
            $recetteController->approveRecette($_POST['id']);
        } elseif ($_POST['action'] === 'reject_recette') {
            $recetteController->rejectRecette($_POST['id']);
        } elseif ($_POST['action'] === 'link_aliment') {
            $db = Database::getConnection();
            $quantite = (float) str_replace(',', '.', (string) ($_POST['quantite'] ?? '0'));
            $stmt = $db->prepare(
                'INSERT INTO recette_aliment (id_recette, id_aliment, quantite_g)
                 VALUES (:id_recette, :id_aliment, :quantite)
                 ON DUPLICATE KEY UPDATE quantite_g = VALUES(quantite_g)'
            );
            $stmt->execute([
                ':id_recette' => (int) $_POST['id_recette'],
                ':id_aliment' => (int) $_POST['id_aliment'],
                ':quantite' => $quantite,
            ]);
        }
    }
    header('Location: aliment.php');
    exit();
}

$aliments = $controller->listAliments();
$recettes = $recetteController->listRecettesByStatus('approved');

try {
    $pendingRecettes = $recetteController->listRecettesByStatus('pending');
} catch (Throwable $e) {
    $pendingRecettes = [];
}

$editAliment = null;
if (isset($_GET['edit'])) {
    $editAliment = $controller->getAliment((int) $_GET['edit']);
}

$editRecette = null;
if (isset($_GET['edit_recette'])) {
    $editRecette = $recetteController->getRecette((int) $_GET['edit_recette']);
}

include __DIR__ . '/aliment.html';
