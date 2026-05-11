<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id']) || strtolower(trim((string) ($_SESSION['role'] ?? ''))) !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);

    exit;
}

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../Services/AchatService.php';
require_once __DIR__ . '/../../Services/UserService.php';

$userId = (int) ($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id invalide']);

    exit;
}

$userService = new UserService();
$achatService = new AchatService();
$user = $userService->getUserById($userId);
$purchases = $achatService->getAchatsByUserId($userId);

$userName = 'Utilisateur';
if ($user) {
    $parts = array_filter([
        trim((string) ($user['nom'] ?? '')),
        trim((string) ($user['prenom'] ?? '')),
    ]);
    $userName = $parts !== [] ? implode(' ', $parts) : (string) ($user['email'] ?? 'Utilisateur');
}

echo json_encode([
    'user_name' => $userName,
    'purchases' => $purchases,
], JSON_UNESCAPED_UNICODE);
