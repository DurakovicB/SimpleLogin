<?php

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */


use OpenApi\Annotations as OA;

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/core/Database.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/../app/controllers/UserController.php';
require __DIR__ . '/../app/controllers/TransactionController.php';
require __DIR__ . '/../app/middleware/JwtMiddleware.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}


Flight::route('POST /auth/register', ['UserController', 'register']);
Flight::route('POST /auth/login', ['UserController', 'login']);

Flight::route('POST /transactions/deposit', ['TransactionController', 'deposit']);

Flight::route('POST /transactions/withdraw', ['TransactionController', 'withdraw']);

Flight::route('GET /transactions/summary', ['TransactionController', 'summary']);

Flight::route('GET /docs', function () {
    header('Content-Type: text/html');
    echo file_get_contents(__DIR__ . '/swagger-ui.html');
});

Flight::route('GET /swagger.php', function () {
    header('Content-Type: application/json');
    echo \OpenApi\Generator::scan([
    __DIR__ . '/../app',
    __DIR__ . '/../app/docs',

    ])->toJson();
});

Flight::start();
