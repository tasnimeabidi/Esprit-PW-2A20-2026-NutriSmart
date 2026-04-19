<?php
// ============================================
// CRUD - FRONT OFFICE : Mise à jour (UPDATE)
// ============================================
// Appelé par 'profile.html' (lors du clic sur "Enregistrer").
// Permet de modifier le poids, la taille, et les objectifs du client en base.
require_once __DIR__ . '/../../controllers/UserController.php';
$controller = new UserController();
$result = $controller->updateProfile();

if ($result) {
    if ($result['success']) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAdmin = isset($_SESSION['role']) && (strtolower($_SESSION['role']) === 'admin' || $_SESSION['role'] === 'Admin');
        
        $redirect = $isAdmin ? '../backoffice/nutrismart-dashboard.html' : 'nutrismart-home.html';
        
        echo "<script>window.location.href='$redirect';</script>";
    } else {
        echo "<script>
            alert('Erreur : " . addslashes($result['message']) . "');
            window.history.back();
        </script>";
    }
}
?>
