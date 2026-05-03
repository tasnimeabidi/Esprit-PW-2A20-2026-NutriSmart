<?php
/**
 * Returns a PDO connection to the NutriSmart database.
 */
function getConnection() {
    $host = 'localhost';
    $db_name = 'nutrismart';
    $username = 'root'; // Default XAMPP username
    $password = '';     // Default XAMPP password

    // Static variable keeps the connection alive so it doesn't reconnect 
    // multiple times during a single page load (Singleton-ish pattern)
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            // In a real app, log this error instead of echoing it
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}
?>