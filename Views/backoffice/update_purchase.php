<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id']) || strtolower(trim((string) ($_SESSION['role'] ?? ''))) !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé']);

    exit;
}

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../Services/AchatService.php';

$id = (int) ($_POST['id'] ?? 0);
$quantite = $_POST['quantite'] ?? '';
$prixTotal = $_POST['prix_total'] ?? '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide', 'received' => ['id' => $id]]);

    exit;
}

$achatService = new AchatService();
$ok = $achatService->updatePurchase($id, $quantite, $prixTotal);

echo json_encode([
    'success' => $ok,
    'received' => ['id' => $id, 'quantite' => $quantite, 'prix_total' => $prixTotal],
]);
