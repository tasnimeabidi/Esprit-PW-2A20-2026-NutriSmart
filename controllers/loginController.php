<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/database.php";

$db = new Database();
$conn = $db->connect();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');

    if (empty($email) || empty($password)) {
        $_SESSION["error"] = "Tous les champs sont obligatoires.";
        header("Location: ../Views/frontoffice/login.php");
        exit();
    }

    $stmt = $conn->prepare("
        SELECT id_utilisateur, nom, mot_de_passe 
        FROM utilisateur 
        WHERE email = ?
    ");

    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["mot_de_passe"])) {

        // 🔥 FIXED SESSION (IMPORTANT)
        $_SESSION["id_utilisateur"] = $user["id_utilisateur"];
        $_SESSION["nom"] = $user["nom"];

        header("Location: ../index.php?action=blog");
        exit();

    } else {
        $_SESSION["error"] = "Email ou mot de passe incorrect.";
        header("Location: ../Views/frontoffice/login.php");
        exit();
    }
}