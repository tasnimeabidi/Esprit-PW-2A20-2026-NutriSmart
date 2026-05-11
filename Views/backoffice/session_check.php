<?php
/**
 * session_check.php (Backoffice)
 * Lit la session PHP et retourne les infos de l'utilisateur connecté en JSON.
 * Utilisé par le dashboard admin pour afficher le nom de l'administrateur connecté.
 */

// Hard disable headers already sent by cleaning output buffer
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chemin vers le Model User depuis le backoffice
require_once __DIR__ . '/../../Models/User.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['loggedIn' => false, 'reason' => 'No session ID']);
    exit;
}

try {
    $userModel = new User();
    $user = $userModel->getById($_SESSION['user_id']);

    if ($user && (!isset($user['is_blocked']) || (int)$user['is_blocked'] !== 1)) {
        
        $role = strtolower(trim($user['role'] ?? ''));
        
        // Final sanity check: This is backoffice, we usually expect Admins here
        // but we'll return info for anyone since the dashboard might be used by others if needed,
        // however we ensure the data is clean.
        
        echo json_encode([
            'loggedIn' => true,
            'name'     => trim($user['nom'] ?? 'Inconnu'),
            'email'    => trim($user['email'] ?? ''),
            'role'     => $user['role']
        ]);
    } else {
        // If blocked or not found
        echo json_encode(['loggedIn' => false, 'reason' => 'User blocked or not found']);
    }
} catch (Exception $e) {
    echo json_encode(['loggedIn' => false, 'error' => $e->getMessage()]);
}
ob_end_flush();
?>
