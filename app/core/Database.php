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

    public static function cleanupTestUsers($email, $username) {
        $db = self::getInstance();  

        $stmt = $db->prepare("DELETE FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $stmt = $db->prepare("DELETE FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
    }
}
