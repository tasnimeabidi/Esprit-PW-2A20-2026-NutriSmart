<?php
require_once 'Models/config.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SELECT id, nom, video_url FROM recette WHERE status = 'approved'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
