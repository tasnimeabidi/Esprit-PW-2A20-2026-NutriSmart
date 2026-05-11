<?php
/**
 * Santé de l’API + test PDO (obligatoire pour que le front utilise MySQL et non le localStorage).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    Database::getConnection()->query('SELECT 1');
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(
        [
            'ok' => false,
            'database' => false,
            'error' => 'Connexion MySQL impossible. Vérifiez MySQL, l’import de database/nutrismart.sql et config/database.php.',
        ],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

http_response_code(200);
echo json_encode(
    [
        'ok' => true,
        'database' => true,
        'service' => 'nutrismart-api',
    ],
    JSON_UNESCAPED_UNICODE
);
