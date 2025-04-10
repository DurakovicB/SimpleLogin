<?php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct($config) {
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";

        $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/config.php';
            self::$instance = new self($config['db']);
        }
        return self::$instance->pdo;
    }
}
