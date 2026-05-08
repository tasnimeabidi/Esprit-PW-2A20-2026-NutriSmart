<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    include_once __DIR__ . '/../../../controllers/RecetteController.php';
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['aliment'])) {
        $controller = new RecetteController();
        $aliment_name = $_GET['aliment'];
        
        if (empty($aliment_name)) {
            // Si la recherche est vide, retourner toutes les recettes approuvées
            $recettes = $controller->listRecettesByStatus('approved');
            echo json_encode(['status' => 'success', 'data' => $recettes]);
        } else {
            // Rechercher les recettes par aliment
            $recettes = $controller->getRecettesByAliment($aliment_name);
            echo json_encode(['status' => 'success', 'data' => $recettes, 'search' => $aliment_name]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Paramètre aliment requis']);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Erreur serveur: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
