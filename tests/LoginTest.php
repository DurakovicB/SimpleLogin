<?php

use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
    private $baseUrl = 'http://localhost:8000';
    private $db;

    private function sendRequest($url, $payload)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

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
    }

    public function testSuccessfulLoginWithEmail()
    {
        $payload = json_encode([
            'email' => 'alice.smith@example.com',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Login successful', $response['message']);
        $this->assertArrayHasKey('token', $response);
    }

    public function testSuccessfulLoginWithUsername()
    {
        $payload = json_encode([
            'username' => 'alice_smith',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Login successful', $response['message']);
        $this->assertArrayHasKey('token', $response);
    }

    public function testLoginInvalidEmail()
    {
        $payload = json_encode([
            'email' => 'nonexistentuser@example.com',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid email/username or password', $response['error']);
    }

    public function testLoginInvalidUsername()
    {
        $payload = json_encode([
            'username' => 'nonexistentuser',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid email/username or password', $response['error']);
    }

    public function testLoginIncorrectPassword()
    {
        $payload = json_encode([
            'email' => 'alice.smith@example.com',
            'password' => 'WrongPassword123'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid email/username or password', $response['error']);
    }

    public function testLoginMissingEmailAndPassword()
    {
        $payload = json_encode([
            'email' => '',
            'password' => ''
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Email/username and password are required', $response['error']);
    }

    public function testLoginMissingEmail()
    {
        $payload = json_encode([
            'email' => '',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Email/username and password are required', $response['error']);
    }

    public function testLoginMissingPassword()
    {
        $payload = json_encode([
            'email' => 'alice.smith@example.com',
            'password' => ''
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/login", $payload);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Email/username and password are required', $response['error']);
    }
}
