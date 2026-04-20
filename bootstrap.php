<?php
/**
 * Point d'amorçage NutriSmart — autoload simple (MVC sans Composer).
 */
declare(strict_types=1);

define('NUTRISMART_BASE', __DIR__);

spl_autoload_register(function (string $class): void {
    $base = NUTRISMART_BASE . DIRECTORY_SEPARATOR;
    $paths = [
        $base . 'Models' . DIRECTORY_SEPARATOR . $class . '.php',
        $base . 'Model' . DIRECTORY_SEPARATOR . $class . '.php',
        $base . 'controllers' . DIRECTORY_SEPARATOR . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

require_once NUTRISMART_BASE . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once NUTRISMART_BASE . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
