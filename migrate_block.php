<?php
require_once __DIR__ . '/config.php';
try {
    $pdo->exec("ALTER TABLE utilisateur ADD COLUMN blocked_at datetime DEFAULT NULL");
    echo "Succès : Colonne blocked_at ajoutée.";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
