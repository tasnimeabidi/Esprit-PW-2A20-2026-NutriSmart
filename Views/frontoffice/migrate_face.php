<?php
require_once __DIR__ . '/config.php';
try {
    $pdo->exec("ALTER TABLE utilisateur ADD COLUMN facial_descriptor TEXT DEFAULT NULL");
    echo "Migration réussie : Colonne facial_descriptor ajoutée.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false || $e->getCode() == '42S21') {
        echo "Les colonnes existent déjà.\n";
    } else {
        echo "Erreur de migration : " . $e->getMessage() . "\n";
    }
}
?>
