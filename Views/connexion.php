<?php
require_once "../config.php";
 
try {
    $pdo = config::getConnexion();
    echo "<p style='color:green;'>Connexion à la base de données réussie !</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Erreur : " . $e->getMessage() . "</p>";
}
?>
 