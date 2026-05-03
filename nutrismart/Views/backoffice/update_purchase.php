<?php
require_once '../../Services/AchatService.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$data = $_POST;
if (empty($data)) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!is_array($data)) {
        parse_str($input, $data);
    }
}

$purchaseId = intval($data['id'] ?? 0);
$quantite = floatval($data['quantite'] ?? 0);
$prixTotal = floatval($data['prix_total'] ?? 0);

if ($purchaseId <= 0 || $quantite <= 0 || $prixTotal < 0) {
    error_log('Invalid update_purchase input: ' . print_r($data, true));
    echo json_encode([
        'success' => false,
        'error' => 'Données invalides',
        'received' => [
            'id' => $data['id'] ?? null,
            'quantite' => $data['quantite'] ?? null,
            'prix_total' => $data['prix_total'] ?? null
        ]
    ]);
    exit;
}

$achatService = new AchatService();
$success = $achatService->updatePurchase($purchaseId, $quantite, $prixTotal);

echo json_encode(['success' => $success]);
