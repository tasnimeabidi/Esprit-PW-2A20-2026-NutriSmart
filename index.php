<?php
require_once 'Controllers/TransactionController.php';

$controller = new TransactionController();
$data = $controller->showDashboard(1);

// Use ?? 0 to show 0 if the value is missing
echo "<h1>Budget: " . ($data['budget']['montant'] ?? 0) . "€</h1>";
echo "<h2>Remaining: " . ($data['solde'] ?? 0) . "€</h2>";

if (empty($data['history'])) {
    echo "<p>No purchase history found.</p>";
} else {
    foreach ($data['history'] as $item) {
        echo "Purchased " . $item['aliment_nom'] . " for " . $item['prix_total'] . "€<br>";
    }
}
?>