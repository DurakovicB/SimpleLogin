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

    if (!isset($data['amount'])){
        Flight::response()->status(400);
        Flight::json(['error' => 'Deposit amount missing']);
        return;
    }

    if($data['amount'] <= 0) {
        Flight::response()->status(400);
        Flight::json(['error' => 'Invalid deposit amount']);
        return;
    }

    JwtMiddleware::verify(); 
    $userId = Flight::get('userId');    
    if (!$userId) {
        return; 
    }

    $db = Database::getInstance();
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (:userId, 'deposit', :amount)");
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->execute();

        $db->commit();

        Flight::json(['message' => 'Deposit successful']);
    } catch (Exception $e) {
        $db->rollBack();
        Flight::response()->status(500);
        Flight::json(['error' => 'Failed to process deposit: ' . $e->getMessage()]);
    }
});

Flight::route('POST /transactions/withdraw', function() {
    $data = Flight::request()->data->getData();
    $withdrawAmount = $data['amount'];

    if (!isset($withdrawAmount) || $withdrawAmount <= 0) {
        Flight::response()->status(400);
        Flight::json(['error' => 'Invalid withdrawal amount']);
        return;
    }

    JwtMiddleware::verify(); 
    $userId = Flight::get('userId');

    $db = Database::getInstance();
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            SELECT SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) AS total_deposit,
                   SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) AS total_withdrawal
            FROM transactions
            WHERE user_id = :userId
        ");
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalDeposit = $result['total_deposit'] ?? 0;
        $totalWithdrawal = $result['total_withdrawal'] ?? 0;
        $currentBalance = $totalDeposit - $totalWithdrawal;

        if ($currentBalance < $withdrawAmount) {
            Flight::response()->status(400);
            Flight::json(['error' => 'Insufficient balance']);
            return;
        }

        //sufficiet funds
        $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (:userId, 'withdraw', :amount)");
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':amount', $withdrawAmount);
        $stmt->execute();

        $db->commit();

        Flight::json(['message' => 'Withdrawal successful']);
    } catch (Exception $e) {
        $db->rollBack();
        Flight::response()->status(500);
        Flight::json(['error' => 'Failed to process withdrawal: ' . $e->getMessage()]);
    }
});

Flight::route('GET /transactions/summary', function() {
    JwtMiddleware::verify();
    $userId = Flight::get('userId');

    $db = Database::getInstance();
    try {
        $stmt = $db->prepare("
            SELECT 
                SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) AS total_deposit,
                SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) AS total_withdrawal,
                COUNT(CASE WHEN type = 'deposit' THEN 1 END) AS deposit_count,
                COUNT(CASE WHEN type = 'withdraw' THEN 1 END) AS withdraw_count
            FROM transactions
            WHERE user_id = :userId
        ");
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalDeposit = $result['total_deposit'] ?? 0;
        $totalWithdrawal = $result['total_withdrawal'] ?? 0;
        $depositCount = $result['deposit_count'] ?? 0;
        $withdrawCount = $result['withdraw_count'] ?? 0;
        $currentBalance = $totalDeposit - $totalWithdrawal;

        $averageDeposit = $depositCount > 0 ? round($totalDeposit / $depositCount, 2) : 0;
        $averageWithdrawal = $withdrawCount > 0 ? round($totalWithdrawal / $withdrawCount, 2) : 0;

        $stmt = $db->prepare("
            SELECT id, type, amount, created_at
            FROM transactions
            WHERE user_id = :userId
            ORDER BY created_at DESC
            LIMIT 7
        ");
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'average_deposit' => $averageDeposit,
            'average_withdrawal' => $averageWithdrawal,
            'total_balance' => $currentBalance,
            'last_transactions' => $transactions
        ];

        Flight::json($summary);
    } catch (Exception $e) {
        Flight::response()->status(500);
        Flight::json(['error' => 'Failed to retrieve account summary: ' . $e->getMessage()]);
    }
});



Flight::route('GET /test', function() {
    Flight::json(['message' => 'working']);
});


Flight::start();
