<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Conexión única (singleton) a MariaDB vía PDO.
 */
class Database
{
    protected static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (static::$pdo instanceof PDO) {
            return static::$pdo;
        }

        $c = Config::get('database');

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $c['driver'],
            $c['host'],
            $c['port'],
            $c['database'],
            $c['charset']
        );

        try {
            static::$pdo = new PDO($dsn, $c['username'], $c['password'], $c['options']);
        } catch (PDOException $e) {
            if (Config::get('app.debug')) {
                throw $e;
            }
            error_log('DB connection error: ' . $e->getMessage());
            http_response_code(500);
            exit('Error de conexión con la base de datos.');
        }

        return static::$pdo;
    }
}
