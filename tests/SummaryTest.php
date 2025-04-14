<?php

use PHPUnit\Framework\TestCase;

class TransactionSummaryTest extends TestCase
{
    private $baseUrl = 'http://localhost:8000';
    private $db;

    private function sendRequest($url, $payload = null, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($payload) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/json']));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $this->fail('cURL error: ' . curl_error($ch));
        }
        curl_close($ch);

        return json_decode($response, true);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->db->prepare("DELETE FROM users WHERE email = :email")
                 ->execute([':email' => 'bob@example.com']);
    }

    public function testUserWithNoTransactions()
    {
        $userEmail = 'bob@example.com';
        $this->db->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)")
                 ->execute([$userEmail, 'bob', password_hash('password123', PASSWORD_DEFAULT)]);

        $payload = json_encode([
            'email' => $userEmail,
            'password' => 'password123'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);
        $this->assertArrayHasKey('token', $response);
        
        $token = $response['token'];

        $summaryResponse = $this->sendRequest("{$this->baseUrl}/transactions/summary", null, [
            'Authorization: Bearer ' . $token
        ]);

        $this->assertEquals(0, $summaryResponse['total_balance']);
        $this->assertEquals(0, $summaryResponse['average_deposit']);
        $this->assertEquals(0, $summaryResponse['average_withdrawal']);
        $this->assertEmpty($summaryResponse['last_transactions']);
    }

    public function testUserWithTransactions()
    {
        $userEmail = 'alice@example.com';
        $username = 'alice';
        $this->db->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)")
                 ->execute([$userEmail, 'alice', password_hash('ValidPass123!', PASSWORD_DEFAULT)]);
        
        $userId = $this->db->lastInsertId();
        $this->db->prepare("INSERT INTO transactions (user_id, type, amount, created_at) VALUES (?, ?, ?, ?)")
                 ->execute([$userId, 'deposit', 500.00, '2025-04-10 15:07:55']);
        $this->db->prepare("INSERT INTO transactions (user_id, type, amount, created_at) VALUES (?, ?, ?, ?)")
                 ->execute([$userId, 'withdraw', 150.00, '2025-04-10 15:07:55']);

        $payload = json_encode([
            'email' => $userEmail,
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);
        $this->assertArrayHasKey('token', $response);
        
        $token = $response['token'];
        $summaryResponse = $this->sendRequest("{$this->baseUrl}/transactions/summary", null, [
            'Authorization: Bearer ' . $token
        ]);

        $this->assertEquals(350.00, $summaryResponse['total_balance']);
        $this->assertEquals(500.00, $summaryResponse['average_deposit']);
        $this->assertEquals(150.00, $summaryResponse['average_withdrawal']);
        $this->assertCount(2, $summaryResponse['last_transactions']);

        Database::cleanupTestUsers($userEmail, $username);
    }


    public function testUnauthenticatedUser()
    {
        $summaryResponse = $this->sendRequest("{$this->baseUrl}/transactions/summary");

        $this->assertArrayHasKey('error', $summaryResponse);
        $this->assertEquals('Authorization header missing', $summaryResponse['error']);
    }

    public function testInvalidToken()
    {
        $invalidToken = 'invalid.jwt.token';
        
        $summaryResponse = $this->sendRequest("{$this->baseUrl}/transactions/summary", null, [
            'Authorization: Bearer ' . $invalidToken
        ]);

        $this->assertArrayHasKey('error', $summaryResponse);
        $this->assertEquals('Invalid or expired token', $summaryResponse['error']);
    }
}
