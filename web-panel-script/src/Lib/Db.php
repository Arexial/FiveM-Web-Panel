<?php
declare(strict_types=1);

namespace App\Lib;

use PDO;
use PDOException;

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = Config::get('db.dsn');
        $user = Config::get('db.user');
        $pass = Config::get('db.pass');

        if (!is_string($dsn) || $dsn === '') {
            throw new PDOException('Database DSN is not configured.');
        }

        self::$pdo = new PDO($dsn, (string)$user, (string)$pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$pdo;
    }
}
