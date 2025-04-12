<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtMiddleware {
    public static function verify() {
        $headers = getallheaders();
    
        if (!isset($headers['Authorization'])) {
            Flight::response()->status(401);
            Flight::json(['error' => 'Authorization header missing']);
            return;
        }
    
        $authHeader = $headers['Authorization'];
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Flight::response()->status(401);
            Flight::json(['error' => 'Invalid Authorization header format']);
            return;
        }
    
        $jwt = $matches[1];
        $config = require __DIR__ . '/../../config/config.php';
        $secretKey = $config['jwt_secret'];
    
        try {
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
            Flight::set('userId', $decoded->userId);
        } catch (Exception $e) {
            Flight::response()->status(401);
            Flight::json(['error' => 'Invalid or expired token']);
            return;
        }
    }
    

    function getAuthorizationHeader() {
        foreach ($_SERVER as $key => $value) {
            if (strtolower($key) === 'http_authorization') {
                return $value;
            }
        }
        return null;
    }
}
