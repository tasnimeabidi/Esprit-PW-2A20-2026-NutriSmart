<?php
require_once 'config/db_connect.php';

// Get the connection object
$db = getConnection();

// Example: Fetch the budget for User ID 1
$query = "SELECT montant, date_creation FROM budget WHERE id_utilisateur = :id";
$stmt = $db->prepare($query);
$stmt->execute(['id' => 1]);
$budget = $stmt->fetch();

if ($budget) {
    echo "The budget is " . $budget['montant'] . " created on " . $budget['date_creation'];
} else {
    echo "No budget found for this user.";
}
?>