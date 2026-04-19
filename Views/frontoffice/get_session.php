<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Models/User.php';

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    $userModel = new User();
    $userData = $userModel->getById($_SESSION['user_id']);
    
    if ($userData) {
        echo json_encode([
            'loggedIn' => true,
            'name' => $userData['nom'],
            'email' => $userData['email'],
            'role' => $_SESSION['role']
        ]);
        exit;
    }
}

echo json_encode(['loggedIn' => false]);
?>
