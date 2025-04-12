<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/core/Database.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/../app/controllers/UserController.php';
require __DIR__ . '/../app/middleware/JwtMiddleware.php';


Flight::route('POST /users/register', ['UserController', 'register']);
Flight::route('POST /users/login', ['UserController', 'login']);

Flight::route('GET /users/info', function () {
    JwtMiddleware::verify();
    $userId = Flight::get('userId');

    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        Flight::json($user);
    } else {
        Flight::json(['error' => 'User not found'], 404);
    }
});



Flight::route('GET /test', function() {
    Flight::json(['message' => 'working']);
});


Flight::start();
