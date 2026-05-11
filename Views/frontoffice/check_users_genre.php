<?php
require_once 'config.php';
global $pdo;
$stmt = $pdo->query("SELECT id_utilisateur, nom, genre FROM utilisateur");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users);
?>

<?php
require_once 'config.php';
global $pdo;
$pdo->exec("UPDATE utilisateur SET genre = 'Femme' WHERE nom LIKE '%Tas%' OR nom LIKE '%tasnime%'");
echo "Base de données mise à jour manuellement pour Tas et tasnime.";
?>
