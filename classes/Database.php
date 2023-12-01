<?php

namespace Classes;

use PDO;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): ?PDO
    {
        if (self::$instance === null) {
            self::$instance = new PDO(
                'sqlsrv:Server=' . config()['server_ip'] . ';Database=' . config()['database'],
                config()['username'],
                config()['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        }

        return self::$instance;
    }
}
