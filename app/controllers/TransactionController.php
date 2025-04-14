<?php


/**
 * @OA\Post(
 *     path="/transactions/deposit",
 *     summary="Deposit money into the user's account",
 *     description="Deposits a specified amount into the user's account after validating the amount and performing necessary checks.",
 *     operationId="deposit",
 *     tags={"Transactions"},
 *     requestBody={
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"amount"},
 *                 @OA\Property(
 *                     property="amount",
 *                     type="number",
 *                     format="float",
 *                     description="The amount to deposit"
 *                 )
 *             )
 *         )
 *     },
 *     responses={
 *         @OA\Response(
 *             response="200",
 *             description="Deposit successful",
 *             @OA\JsonContent(
 *                 type="object",
 *                 @OA\Property(property="message", type="string", example="Deposit successful")
 *             )
 *         ),
 *         @OA\Response(
 *             response="400",
 *             description="Bad request - Validation errors",
 *             @OA\JsonContent(
 *                 type="object",
 *                 @OA\Property(property="error", type="string", example="Deposit amount missing")
 *             )
 *         ),
 *         @OA\Response(
 *             response="500",
 *             description="Internal server error - Failed to process deposit",
 *             @OA\JsonContent(
 *                 type="object",
 *                 @OA\Property(property="error", type="string", example="Failed to process deposit: [error_message]")
 *             )
 *         )
 *     },
 *     security={
 *         {
 *             "BearerAuth": {}
 *         }
 *     }
 * )
 */
class TransactionController {
    public function deposit(){
            $data = Flight::request()->data->getData();
        
            if (!isset($data['amount'])){
                Flight::response()->status(400);
                Flight::json(['error' => 'Deposit amount missing']);
                return;
            }
        
            if (!is_numeric($data['amount']) || filter_var($data['amount'], FILTER_VALIDATE_FLOAT) === false) {
                Flight::response()->status(400);
                Flight::json(['error' => 'Amount must be a valid number']);
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
    }
    /**
     * @OA\Post(
     *     path="/transactions/withdraw",
     *     summary="Withdraw money from the user's account",
     *     description="Withdraws a specified amount from the user's account after validating the withdrawal amount and checking for sufficient balance.",
     *     operationId="withdraw",
     *     tags={"Transactions"},
     *     requestBody={
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"amount"},
     *                 @OA\Property(
     *                     property="amount",
     *                     type="number",
     *                     format="float",
     *                     description="The amount to withdraw"
     *                 )
     *             )
     *         )
     *     },
     *     responses={
     *         @OA\Response(
     *             response="200",
     *             description="Withdrawal successful",
     *             @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Withdrawal successful")
     *             )
     *         ),
     *         @OA\Response(
     *             response="400",
     *             description="Bad request - Validation errors or insufficient balance",
     *             @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Withdrawal amount missing")
     *             )
     *         ),
     *         @OA\Response(
     *             response="500",
     *             description="Internal server error - Failed to process withdrawal",
     *             @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Failed to process withdrawal: [error_message]")
     *             )
     *         )
     *     },
     *     security={
     *         {
     *             "BearerAuth": {}
     *         }
     *     }
     * )
     */
    public function withdraw(){
        $data = Flight::request()->data->getData();

        if (!isset($data['amount'])){
            Flight::response()->status(400);
            Flight::json(['error' => 'Withdrawal amount missing']);
            return;
        }

        if ($data['amount'] <= 0) {
            Flight::response()->status(400);
            Flight::json(['error' => 'Invalid withdrawal amount']);
            return;
        }

        if (!is_numeric($data['amount']) || filter_var($data['amount'], FILTER_VALIDATE_FLOAT) === false) {
            Flight::response()->status(400);
            Flight::json(['error' => 'Amount must be a valid number']);
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

            if ($currentBalance < $data['amount']) {
                Flight::response()->status(400);
                Flight::json(['error' => 'Insufficient balance']);
                return;
            }

            //sufficiet funds
            $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (:userId, 'withdraw', :amount)");
            $stmt->bindParam(':userId', $userId);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->execute();

            $db->commit();

            Flight::json(['message' => 'Withdrawal successful']);
        } catch (Exception $e) {
            $db->rollBack();
            Flight::response()->status(500);
            Flight::json(['error' => 'Failed to process withdrawal: ' . $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     *     path="/transactions/summary",
     *     summary="Get account summary",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Summary of user's account",
     *         @OA\JsonContent(
     *             @OA\Property(property="average_deposit", type="number", example=100.50),
     *             @OA\Property(property="average_withdrawal", type="number", example=50.25),
     *             @OA\Property(property="total_balance", type="number", example=200.00),
     *             @OA\Property(
     *                 property="last_transactions",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="deposit"),
     *                     @OA\Property(property="amount", type="number", example=100.00),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-04-14T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to retrieve account summary")
     *         )
     *     )
     * )
     */
    public function summary(){
        JwtMiddleware::verify();
        $userId = Flight::get('userId');
        if (!$userId) {
            return; 
        }

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
    }
}
