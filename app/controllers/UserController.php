<?php
class UserController {
    public static function register() {
        $data = json_decode(file_get_contents('php://input'), true);

        Flight::json(['message' => 'Register route']);
    }

    public static function login() {
        $data = json_decode(file_get_contents('php://input'), true);

        Flight::json(['token' => 'fake-jwt']);
    }
}
