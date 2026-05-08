<?php
include_once '../../controllers/AlimentController.php';
include_once '../../controllers/RecetteController.php';
include_once '../../Models/config.php';

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
            // Lier un aliment à une recette
            $db = (new Database())->getConnection();
            $query = "INSERT INTO recette_aliment (id_recette, id_aliment, quantite_g) 
                      VALUES (:id_recette, :id_aliment, :quantite)
                      ON DUPLICATE KEY UPDATE quantite_g = :quantite2";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_recette', $_POST['id_recette']);
            $stmt->bindParam(':id_aliment', $_POST['id_aliment']);
            $stmt->bindParam(':quantite', $_POST['quantite']);
            $stmt->bindParam(':quantite2', $_POST['quantite']);
            $stmt->execute();
        }
    }
    header("Location: aliment.php");
    exit();
}

$aliments = $controller->listAliments();
$recettes = $recetteController->listRecettesByStatus('approved');
$pendingRecettes = $recetteController->listRecettesByStatus('pending');


// Si ?edit=ID ou ?edit_recette=ID dans l'URL
$editAliment = null;
if (isset($_GET['edit'])) {
    $editAliment = $controller->getAliment((int)$_GET['edit']);
}

$editRecette = null;
if (isset($_GET['edit_recette'])) {
    $editRecette = $recetteController->getRecette((int)$_GET['edit_recette']);
}

// Charger la vue (le HTML séparé)
include 'aliment-view.php';
?>
