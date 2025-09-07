<?php
declare(strict_types=1);

namespace App\core;

use PDO;
use PDOException;

abstract class Model
{
    private static ?PDO $pdo = null;

    protected function db(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                die('Erreur DB : ' . e($e->getMessage()));
            }
        }
        return self::$pdo;
    }
}
