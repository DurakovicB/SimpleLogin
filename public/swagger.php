<?php
use OpenApi\Annotations as OA;
use OpenApi\Generator;


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/middleware/JwtMiddleware.php';
require __DIR__ . '/../app/controllers/TransactionController.php';

header('Content-Type: application/json');

echo \OpenApi\Generator::scan([
    __DIR__ . '/../app',
    __DIR__ . '/../app/docs',

    ])->toJson();
