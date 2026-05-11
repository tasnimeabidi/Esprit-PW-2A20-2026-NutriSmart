<?php
/**
 * Accès base de données (singleton PDO).
 */
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        /** @var array{dsn:string,user:string,pass:string,options:array<int,mixed>} $cfg */
        $cfg = require NUTRISMART_BASE . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        self::$pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass'], $cfg['options']);
        return self::$pdo;
    }
}
