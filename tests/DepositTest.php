<?php

use PHPUnit\Framework\TestCase;

class DepositTest extends TestCase
{
    private $baseUrl = 'http://localhost:8000';
    private $db;
    private $token;
    private $testEmail = 'deposit.user@example.com';
    private $testUsername = 'deposituser';

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

        $passwordHash = password_hash('depositpass', PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
        $stmt->execute([$this->testEmail, $this->testUsername, $passwordHash]);

        $payload = json_encode([
            'email' => $this->testEmail,
            'password' => 'depositpass'
        ]);
        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);
        $this->token = $response['token'];
    }

    public function tearDown(): void
    {
        Database::cleanupTestUsers($this->testEmail, $this->testUsername);
    }

    public function testSuccessfulDeposit()
    {
        $payload = json_encode(['amount' => 100.50]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/deposit", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Deposit successful', $response['message']);
    }

    public function testDepositMissingAmount()
    {
        $payload = json_encode([]); // no amount

        $response = $this->sendRequest("{$this->baseUrl}/transactions/deposit", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Deposit amount missing', $response['error']);
    }

    public function testDepositNonNumericAmount()
    {
        $payload = json_encode(['amount' => 'abc']);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/deposit", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Amount must be a valid number', $response['error']);
    }

    public function testDepositInvalidFloat()
    {
        $payload = json_encode(['amount' => '12.12.12']);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/deposit", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Amount must be a valid number', $response['error']);
    }

    public function testDepositZeroAmount()
    {
        $payload = json_encode(['amount' => 0]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/deposit", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid deposit amount', $response['error']);
    }

    public function testDepositNegativeAmount()
    {
        $payload = json_encode(['amount' => -10]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/deposit", $payload, [
            "Authorization: Bearer {$this->token}"
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid deposit amount', $response['error']);
    }

    public function testUnauthorizedDeposit()
    {
        $payload = json_encode(['amount' => 50.00]);

        $response = $this->sendRequest("{$this->baseUrl}/transactions/deposit", $payload, [
            // no authorization header
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Authorization header missing', $response['error']);
    }

    public function testInvalidToken()
    {
        $payload = json_encode(['amount' => 50.00]);

        $invalidToken = 'invalid.jwt.token';
        
        $response = $this->sendRequest("{$this->baseUrl}/transactions/deposit", $payload, [
            'Authorization: Bearer ' . $invalidToken
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid or expired token', $response['error']);
    }
}
