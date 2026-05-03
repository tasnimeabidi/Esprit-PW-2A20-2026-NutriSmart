<?php
require_once '../../Services/AchatService.php';
require_once '../../Services/UserService.php';

if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    $achatService = new AchatService();
    $userService = new UserService();
    
    $purchases = $achatService->getUserHistory($userId);
    $user = $userService->getUserById($userId);
    
    header('Content-Type: application/json');
    echo json_encode([
        'user_name' => $user['nom'],
        'purchases' => $purchases
    ]);
}
?>