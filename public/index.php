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


Flight::route('POST /auth/register', ['UserController', 'register']);
Flight::route('POST /auth/login', ['UserController', 'login']);

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

Flight::route('POST /transactions/deposit', function() {
    $data = Flight::request()->data->getData();
    $depositAmount = $data['amount'];

    if (!isset($depositAmount) || $depositAmount <= 0) {
        Flight::response()->status(400);
        Flight::json(['error' => 'Invalid deposit amount']);
        return;
    }

    JwtMiddleware::verify(); 
    $userId = Flight::get('userId');

    $db = Database::getInstance();
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (:userId, 'deposit', :amount)");
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':amount', $depositAmount);
        $stmt->execute();

        $db->commit();

        Flight::json(['message' => 'Deposit successful']);
    } catch (Exception $e) {
        $db->rollBack();
        Flight::response()->status(500);
        Flight::json(['error' => 'Failed to process deposit: ' . $e->getMessage()]);
    }
});





Flight::route('GET /test', function() {
    Flight::json(['message' => 'working']);
});


Flight::start();
