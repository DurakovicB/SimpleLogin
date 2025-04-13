<?php
use \Firebase\JWT\JWT;
use ZxcvbnPhp\Zxcvbn;

class UserController {
    public function register() {
        $data = Flight::request()->data->getData();

        if (empty($data['email']) || empty($data['password']) || empty($data['username'])) {
            Flight::json(['error' => 'Email, password, and username are required'], 400);
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Flight::json(['error' => 'Invalid email'], 400);
            return;
        }

        $emailDomain = substr(strrchr($data['email'], "@"), 1); 

        if (!checkdnsrr($emailDomain, "MX")) {
            Flight::json(['error' => 'Email domain does not accept emails'], 400);
            return;
        }

        if (strlen($data['password']) < 6) {
            Flight::json(['error' => 'Password must have at least 6 characters'], 400);
            return;
        }

        $passHasLower = preg_match('/[a-z]/', $data['password']);
        $passHasUpper = preg_match('/[A-Z]/', $data['password']);
        $passHasDigit = preg_match('/[0-9]/', $data['password']);
        $passHasSpecial = preg_match('/[\W_]/', $data['password']);

        if (!($passHasLower && $passHasUpper && $passHasDigit && $passHasSpecial)){
            Flight::json(['error' => 'Password must include uppercase, lowercase, digit, and special characters.'], 400);
            return;
        }


        $zxcvbn = new Zxcvbn();
        $strength = $zxcvbn->passwordStrength($data['password']);

        if ($strength['score'] < 3) {
            Flight::json(['error' => 'Password is too weak.'], 400);
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

        if (strlen($data['username']) < 4) {
            Flight::json(['error' => 'Username must have at least 4 characters'], 400);
            return;
        }

        if (strlen($data['username']) > 20) {
            Flight::json(['error' => 'Username must not have more than 20 characters'], 400);
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])){
            Flight::json(['error' => 'Username must only have letters, numbers, and underscores'], 400);
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

        $data['email'] = isset($data['email']) ? strtolower(trim($data['email'])) : null;
        $data['username'] = isset($data['username']) ? trim($data['username']) : null;
        $data['password'] = isset($data['password']) ? trim($data['password']) : null;



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
