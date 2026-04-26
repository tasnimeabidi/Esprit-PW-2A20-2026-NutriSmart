<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nutrismart');

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'clash.bouallegue@gmail.com');
define('SMTP_PASS', 'jiuq alsm cega jvgt');
define('SMTP_FROM', 'clash.bouallegue@gmail.com');
define('SMTP_FROM_NAME', 'NutriSmart');

// OAuth Configuration (Google)
define('OAUTH_GOOGLE_CLIENT_ID', '783998000082-6njhv9j0m41nheki10e7293n0rovm7l2.apps.googleusercontent.com');
define('OAUTH_GOOGLE_CLIENT_SECRET', 'VOTRE_SECRET_GOOGLE_ICI_POUR_GITHUB');

// OAuth Configuration (Facebook)
define('OAUTH_FACEBOOK_CLIENT_ID', '1296056348517578');
define('OAUTH_FACEBOOK_CLIENT_SECRET', '8336b4a87a0c73dbd71eb1bb44e819fa');
    
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
