<?php
require_once '../../Services/AchatService.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$purchaseId = intval($data['id'] ?? 0);
$quantite = intval($data['quantite'] ?? 0);
$prixTotal = floatval($data['prix_total'] ?? 0);

if ($purchaseId <= 0 || $quantite <= 0 || $prixTotal < 0) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

$achatService = new AchatService();
$success = $achatService->updatePurchase($purchaseId, $quantite, $prixTotal);

echo json_encode(['success' => $success]);
