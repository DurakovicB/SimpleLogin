<?php

use PHPUnit\Framework\TestCase;

class WithdrawTest extends TestCase
{
    private $baseUrl = 'http://localhost:8000';
    private $db;
    private $token;
    private $testEmail = 'withdraw.user@example.com';
    private $testUsername = 'withdrawuser';

    private function sendRequest($url, $payload, $headers = [])
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Content-Type: application/json'], $headers));
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
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
        $this->db = Database::getInstance();

        $passwordHash = password_hash('withdrawpass', PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
        $stmt->execute([$this->testEmail, $this->testUsername, $passwordHash]);

        $payload = json_encode([
            'email' => $this->testEmail,
            'password' => 'withdrawpass'
        ]);
        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);
        $this->token = $response['token'];

        $depositPayload = json_encode(['amount' => 200]);
        $this->sendRequest("{$this->baseUrl}/transactions/deposit", $depositPayload, [
            "Authorization: Bearer {$this->token}"
        ]);
    }

    public function tearDown(): void
    {
        Database::cleanupTestUsers($this->testEmail, $this->testUsername);
    }

    public function testSuccessfulWithdrawal()
    {
        $payload = json_encode(['amount' => 100]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/withdraw", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Withdrawal successful', $response['message']);
    }

    public function testWithdrawalMissingAmount()
    {
        $payload = json_encode([]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/withdraw", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Withdrawal amount missing', $response['error']);
    }

    public function testWithdrawalNonNumericAmount()
    {
        $payload = json_encode(['amount' => 'banana']);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/withdraw", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Amount must be a valid number', $response['error']);
    }

    public function testWithdrawalInvalidFloat()
    {
        $payload = json_encode(['amount' => '10.10.10']);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/withdraw", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Amount must be a valid number', $response['error']);
    }

    public function testWithdrawalZeroAmount()
    {
        $payload = json_encode(['amount' => 0]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/withdraw", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid withdrawal amount', $response['error']);
    }

    public function testWithdrawalNegativeAmount()
    {
        $payload = json_encode(['amount' => -50]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/withdraw", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid withdrawal amount', $response['error']);
    }

    public function testSqlInjectionAttempt()
    {
        $payload = json_encode(['amount' => "100; DROP TABLE users;"]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/withdraw", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Amount must be a valid number', $response['error']);
    }


    public function testWithdrawalInsufficientFunds()
    {
        $payload = json_encode(['amount' => 999999]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/withdraw", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Insufficient balance', $response['error']);
    }

    public function testUnauthorizedWithdrawal()
    {
        $payload = json_encode(['amount' => 50.00]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/withdraw", $payload, [
            // no authorization header
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Authorization header missing', $response['error']);
    }

    public function testInvalidToken()
    {
        $payload = json_encode(['amount' => 50.00]);

        $invalidToken = 'invalid.jwt.token';
        
        $response = $this->sendRequest("{$this->baseUrl}/transactions/withdraw", $payload, [
            'Authorization: Bearer ' . $invalidToken
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid or expired token', $response['error']);
    }
}
