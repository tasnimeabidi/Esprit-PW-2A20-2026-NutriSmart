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

$raw = file_get_contents('php://input');
$data = is_string($raw) ? json_decode($raw, true) : null;
$id = (int) (is_array($data) ? ($data['id'] ?? 0) : 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);

    exit;
}

$achatService = new AchatService();
$ok = $achatService->deletePurchase($id);

echo json_encode(['success' => $ok]);
