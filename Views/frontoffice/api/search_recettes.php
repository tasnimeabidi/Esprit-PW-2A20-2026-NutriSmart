<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(__DIR__, 3) . '/controllers/RecetteController.php';

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['aliment'])) {
        $controller = new RecetteController();
        $aliment_name = (string) $_GET['aliment'];

        if ($aliment_name === '') {
            $recettes = $controller->listRecettesByStatus('approved');
            echo json_encode(['status' => 'success', 'data' => $recettes]);
        } else {
            $recettes = $controller->getRecettesByAliment($aliment_name);
            echo json_encode(['status' => 'success', 'data' => $recettes, 'search' => $aliment_name]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Paramètre aliment requis']);
    }
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur serveur: ' . $e->getMessage(),
    ]);
}
