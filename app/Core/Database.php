<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

            try {
                self::$connection = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $exception) {
                app_log('error', 'Erro de conexão com banco', [
                    'error' => $exception->getMessage(),
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                ]);

                http_response_code(500);

                if (APP_DEBUG) {
                    exit('Erro de conexão com banco de dados: ' . $exception->getMessage());
                }

                exit('Não foi possível conectar ao banco de dados no momento. Tente novamente em instantes.');
            }
        }

        return self::$connection;
    }
}
