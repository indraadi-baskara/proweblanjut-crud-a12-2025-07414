<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?self $instance = null;
    private readonly PDO $pdo;

    private function __construct()
    {
        $config = require dirname(__DIR__, 2) . "/config/database.php";

        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=%s",
            $config["host"],
            $config["port"],
            $config["dbname"],
            $config["charset"],
        );

        try {
            $this->pdo = new PDO($dsn, $config["user"], $config["pass"], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Never expose raw PDO messages to the outside world
            throw new \RuntimeException(
                "Database connection failed.",
                previous: $e,
            );
        }
    }

    public static function getInstance(): self
    {
        self::$instance ??= new self();
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // Prevent cloning and unserialization of the singleton
    private function __clone(): void {}
    public function __wakeup(): never
    {
        throw new \RuntimeException("Cannot unserialize a singleton.");
    }
}
