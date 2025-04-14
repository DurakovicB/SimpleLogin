<?php

use PHPUnit\Framework\TestCase;
require __DIR__ . '/../app/core/Database.php';

class RegisterTest extends TestCase
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
        
        Database::cleanupTestUsers('testuser@example.com', 'testuser');
        
        $this->db = Database::getInstance();
        $this->db->beginTransaction();
    }

    public function tearDown(): void
    {
        $this->db->rollBack();
        parent::tearDown();
    }

    public function testSuccessfulRegister()
    {
        $payload = json_encode([
            'email' => 'testuser@example.com',
            'username' => 'testuser',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('User registered successfully', $response['message']);
    }

    public function testRegisterMissingFields()
    {
        $payload = json_encode([
            'username' => 'testuser',
            'password' => 'ValidPass123!'
            // missing email
        ]);
        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Email, password, and username are required', $response['error']);

        $payload = json_encode([
            'email' => 'testuser@example.com',
            'username' => 'testuser'
            // missing password
        ]);
        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Email, password, and username are required', $response['error']);

        $payload = json_encode([
            'email' => 'testuser@example.com',
            'password' => 'ValidPass123!'
            // missing username
        ]);
        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Email, password, and username are required', $response['error']);
    }

    public function testRegisterInvalidEmail()
    {
        $payload = json_encode([
            'email' => 'invalid-email',
            'username' => 'testuser',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid email', $response['error']);
    }

    public function testRegisterInvalidEmailDomain()
    {
        $payload = json_encode([
            'email' => 'testuser@nonexistentdomain.com',
            'username' => 'testuser',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Email domain does not accept emails', $response['error']);
    }

    public function testRegisterWeakPassword()
    {
        $payload = json_encode([
            'email' => 'testuser@example.com',
            'username' => 'testuser',
            'password' => 'weak'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Password must have at least 6 characters', $response['error']);
    }

    public function testRegisterInvalidPasswordFormat()
    {
        $payload = json_encode([
            'email' => 'testuser@example.com',
            'username' => 'testuser',
            'password' => 'password'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Password must include uppercase, lowercase, digit, and special characters.', $response['error']);
    }

    public function testRegisterUsernameTooShort()
    {
        $payload = json_encode([
            'email' => 'testuser1@example.com',
            'username' => 'usr',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Username must have at least 4 characters', $response['error']);
    }

    public function testRegisterUsernameTooLong()
    {
        $payload = json_encode([
            'email' => 'testuser2@example.com',
            'username' => 'thisisaverylongusername',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Username must not have more than 20 characters', $response['error']);
    }

    public function testRegisterInvalidUsernameCharacters()
    {
        $payload = json_encode([
            'email' => 'testuser3@example.com',
            'username' => 'test@user',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Username must only have letters, numbers, and underscores', $response['error']);
    }

    public function testRegisterEmailAlreadyExists()
    {
        $payload = json_encode([
            'email' => 'dave.white@example.com',
            'username' => 'newuser',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Email already exists', $response['error']);
    }

    public function testRegisterUsernameAlreadyExists()
    {
        $payload = json_encode([
            'email' => 'newuser@example.com',
            'username' => 'dave_white',
            'password' => 'ValidPass123!'
        ]);

        $response = $this->sendRequest("{$this->baseUrl}/auth/register", $payload);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Username already exists', $response['error']);
    }
}
