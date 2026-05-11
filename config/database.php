<?php
/**
 * Paramètres PDO — à adapter à votre environnement (XAMPP, etc.).
 */
declare(strict_types=1);

return [
    'dsn' => 'mysql:host=127.0.0.1;dbname=nutrismart;charset=utf8mb4',
    'user' => 'root',
    'pass' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
];
