<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/core/Database.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/../app/controllers/UserController.php';

Flight::route('POST /users/register', ['UserController', 'register']);
Flight::route('POST /users/login', ['UserController', 'login']);

Flight::route('GET /test', function() {
    Flight::json(['message' => 'working']);
});


Flight::start();
