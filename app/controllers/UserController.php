<?php
    use \Firebase\JWT\JWT;

class UserController {
    public function register() {
        $data = Flight::request()->data->getData();

        if (empty($data['email']) || empty($data['password'] || empty($data['username']))) {
            Flight::json(['error' => 'Email, password, and username are required'], 400);
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

    public function login() {
        $data = Flight::request()->data->getData();

        if ((empty($data['email']) && empty($data['username'])) || empty($data['password'])) {
            Flight::json(['error' => 'Email/username and password are required'], 400);
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email OR username = :username");
        $stmt->bindParam(':email', $data['email']); 
        $stmt->bindParam(':username', $data['username']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Flight::json(['error' => 'Invalid email/username or password'], 400);
            return;
        }

        if (!password_verify($data['password'], $user['password'])) {
            Flight::json(['error' => 'Invalid email/username or password'], 400);
            return;
        }

        $jwt = $this->generateJWT($user['id'], $user['email']); 

        Flight::json(['message' => 'Login successful', 'token' => $jwt], 200);
    }

    private function generateJWT($userId, $email) {
        $config = require __DIR__ . '/../../config/config.php';
        
        $key = $config['jwt_secret'];
        $issuedAt = time();
        $expirationTime = $issuedAt + 3000;
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'userId' => $userId,
            'email' => $email,
        ];

        try {
            $jwt = JWT::encode($payload, $key, 'HS256');
            return $jwt;
        } catch (Exception $e) {
            return 'Failed to encode JWT: ' . $e->getMessage();
        }
    }

}
