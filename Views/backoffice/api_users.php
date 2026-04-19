<?php
require_once __DIR__ . '/../../controllers/UserController.php';
header('Content-Type: application/json');

$controller = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
            $success = $controller->deleteUser($_GET['id']);
            echo json_encode(['success' => $success]);
            exit;
        }
    }
    
    // Default GET: list all users
    $users = $controller->listUsers();
    echo json_encode($users);
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'update') {
            $result = $controller->adminUpdateUser();
            echo json_encode($result);
            exit;
        } else if ($_GET['action'] === 'create') {
            $result = $controller->adminCreateUser();
            echo json_encode($result);
            exit;
        }
    }
}
?>
