<?php
// filepath: c:\dev\giova\backend\tests\Functional\AuthTest.php

namespace Tests\Functional;

class AuthTest extends BaseTestCase
{
    /**
     * Test successful login with valid credentials
     */
    public function testUserLogin()
    {
        // Create a test user
        $user = [
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'user'
        ];
        
        // Insert the user directly into the database
        $this->createTestUser($user);
        
        // Make a login request
        $response = $this->runApp('POST', '/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        // Verify response
        $this->assertEquals(200, $response->getStatusCode());        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('access_token', $body);
        $this->assertArrayHasKey('refresh_token', $body);
        $this->assertArrayHasKey('user', $body);
        $this->assertEquals('test@example.com', $body['user']['email']);
        $this->assertEquals('Test', $body['user']['first_name']);
        $this->assertEquals('User', $body['user']['last_name']);
        $this->assertEquals('user', $body['user']['role']);
    }
    
    /**
     * Test login with invalid credentials
     */
    public function testInvalidCredentials()
    {
        // Create a test user
        $user = [
            'email' => 'test2@example.com',
            'password' => password_hash('correctpassword', PASSWORD_DEFAULT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'user'
        ];
        
        // Insert the user directly into the database
        $this->createTestUser($user);
        
        // Make a login request with wrong password
        $response = $this->runApp('POST', '/api/login', [
            'email' => 'test2@example.com',
            'password' => 'wrongpassword'
        ]);
        
        // Verify response
        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertEquals('Invalid email or password', $body['message']);
    }
    
    /**
     * Test accessing protected endpoint without authentication
     */
    public function testProtectedEndpointWithoutAuth()
    {
        // Attempt to access protected endpoint without token
        $response = $this->runApp('GET', '/api/users');
        
        // Verify response
        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertEquals('Authorization token required', $body['message']);
    }
      /**
     * Test token refresh functionality
     */
    public function testTokenRefresh()
    {
        // Create a test user
        $user = [
            'email' => 'refresh@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => 'Refresh',
            'last_name' => 'Test',
            'role' => 'user'
        ];
        
        // Insert the user directly into the database
        $userId = $this->createTestUser($user);
        
        // Login to get refresh token
        $response = $this->runApp('POST', '/api/login', [
            'email' => 'refresh@example.com',
            'password' => 'password123'
        ]);
        
        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('refresh_token', $body);
        $refreshToken = $body['refresh_token'];
        
        // Use refresh token to get new access token
        $response = $this->runApp('POST', '/api/refresh', [
            'refresh_token' => $refreshToken
        ]);
        
        // Verify response
        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('access_token', $body);
    }
    
    /**
     * Test logout functionality
     */
    public function testLogout()
    {
        // Create a test user
        $user = [
            'email' => 'logout@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => 'Logout',
            'last_name' => 'Test',
            'role' => 'user'
        ];
        
        // Insert the user directly into the database
        $userId = $this->createTestUser($user);
        
        // Login to get refresh token
        $response = $this->runApp('POST', '/api/login', [
            'email' => 'logout@example.com',
            'password' => 'password123'
        ]);
        
        $body = json_decode((string)$response->getBody(), true);
        $refreshToken = $body['refresh_token'];
        
        // Logout using refresh token
        $response = $this->runApp('POST', '/api/logout', [
            'refresh_token' => $refreshToken
        ]);
        
        // Verify response
        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['success']);
        
        // Try to use the refresh token again, should fail
        $response = $this->runApp('POST', '/api/refresh', [
            'refresh_token' => $refreshToken
        ]);
        
        // Verify token is no longer valid
        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['error']);
    }

    /**
     * Test role-based access control
     */
    public function testRoleBasedAccess()
    {
        // Create a regular user
        $regularUser = [
            'email' => 'user@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => 'Regular',
            'last_name' => 'User',
            'role' => 'user'
        ];
        
        // Insert the user directly into the database
        $userId = $this->createTestUser($regularUser);
        
        // Login as regular user
        $response = $this->runApp('POST', '/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123'
        ]);
        
        $body = json_decode((string)$response->getBody(), true);
        $token = $body['access_token'];
        
        // Try to access admin-only endpoint to create a new user
        $response = $this->runApp('POST', '/api/users', [
            'email' => 'newuser@example.com',
            'password' => 'newpassword',
            'first_name' => 'New'
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        
        // Verify access is forbidden
        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertEquals('Forbidden: Insufficient permissions', $body['message']);
    }
    
    /**
     * Helper method to create a test user
     */
    private function createTestUser(array $userData)
    {
        // Use the User model to create a user
        $user = \User::create($userData);
        return $user->id;
    }
}
