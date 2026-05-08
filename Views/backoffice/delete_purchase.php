<?php
require_once '../../Services/AchatService.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!is_array($data)) {
    parse_str($input, $data);
}
if (!is_array($data)) {
    $data = $_POST;
}
$purchaseId = intval($data['id'] ?? 0);

if ($purchaseId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID d\'achat invalide']);
    exit;
}

$achatService = new AchatService();
$success = $achatService->deletePurchase($purchaseId);

echo json_encode(['success' => $success]);
