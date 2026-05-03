<?php
include_once 'Models/config.php';
$db = (new Database())->getConnection();

echo "--- DIAGNOSTIC NUTRISMART ---\n";

// Check Users
$user = $db->query("SELECT * FROM utilisateur LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "Utilisateur Actif: ID=" . ($user['id_utilisateur'] ?? 'AUCUN') . " Nom=" . ($user['nom'] ?? 'N/A') . "\n";

$uid = $user['id_utilisateur'] ?? 1;
$today = date('Y-m-d');

// Check Nutrition
$nut = $db->prepare("SELECT * FROM journal_nutrition WHERE id_utilisateur = ?");
$nut->execute([$uid]);
$rows = $nut->fetchAll(PDO::FETCH_ASSOC);
echo "Total entrées nutrition en base pour cet ID: " . count($rows) . "\n";
foreach($rows as $r) {
    echo "  - Date: " . $r['date_entree'] . " | Cal: " . $r['calories'] . " | ID_Aliment: " . $r['id_aliment'] . "\n";
}

// Check Stats Query
$stmt = $db->prepare("SELECT SUM(calories) FROM journal_nutrition WHERE id_utilisateur = ? AND DATE(date_entree) = ?");
$stmt->execute([$uid, $today]);
$sum = $stmt->fetchColumn();
echo "Calcul 'Today' (PHP=". $today ."): " . ($sum ?: 0) . " kcal\n";

echo "--- FIN DU DIAGNOSTIC ---\n";
?>
