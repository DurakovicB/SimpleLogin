<?php
class UserController {
    public function register() {
        $data = Flight::request()->data->getData();

        if (empty($data['email']) || empty($data['password'] || empty($data['username']))) {
            Flight::json(['error' => 'Email and password are required'], 400);
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Flight::json(['error' => 'Invalid email'], 400);
            return;
        }

        if (strlen($data['password']) < 6) {
            Flight::json(['error' => 'Password must have at least 6 characters'], 400);
            return;
        }

        $db = Database::getInstance();

    $emailCheckSql = "SELECT COUNT(*) FROM users WHERE email = :email";
    $emailCheckStmt = $db->prepare($emailCheckSql);
    $emailCheckStmt->bindParam(':email', $data['email']);
    $emailCheckStmt->execute();

    if ($emailCheckStmt->fetchColumn() > 0) {
        Flight::json(['error' => 'Email already exists'], 409);
        return;
    }

    $usernameCheckSql = "SELECT COUNT(*) FROM users WHERE username = :username";
    $usernameCheckStmt = $db->prepare($usernameCheckSql);
    $usernameCheckStmt->bindParam(':username', $data['username']);
    $usernameCheckStmt->execute();

    if ($usernameCheckStmt->fetchColumn() > 0) {
        Flight::json(['error' => 'Username already exists'], 409);
        return;
    }

    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (email, username, password) VALUES (:email, :username, :password)";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':password', $hashedPassword);

    if ($stmt->execute()) {
        Flight::json(['message' => 'User registered successfully'], 201);
    } else {
        Flight::json(['error' => 'Error registering user'], 500);
    }
}

    public static function login() {
        $data = Flight::request()->data->getData();

        Flight::json(['token' => 'fake-jwt']);
    }
}
