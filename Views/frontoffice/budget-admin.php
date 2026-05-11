<?php
/**
 * Point d'entrée public pour l'admin budget si Apache refuse Views/backoffice (403).
 * Préfixe web vers les API JSON dans backoffice/.
 */
declare(strict_types=1);

if (!defined('NUTRISMART_BO_WEB')) {
    $sn = $_SERVER['SCRIPT_NAME'] ?? '';
    $sn = str_replace('\\', '/', (string) $sn);
    $dir = rtrim(dirname($sn), '/');
    define('NUTRISMART_BO_WEB', $dir . '/../backoffice/');
}

require __DIR__ . '/../backoffice/budget-admin.php';
