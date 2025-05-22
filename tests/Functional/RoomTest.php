<?php
// filepath: c:\dev\giova\backend\tests\Functional\RoomTest.php

namespace Tests\Functional;

class RoomTest extends BaseTestCase
{
    /**
     * @var string JWT token for authenticated requests
     */
    private $accessToken;
    
    /**
     * @var int User ID for the test user
     */
    private $userId;
    
    /**
     * @var int Company ID for the test company
     */
    private $companyId;
    
    /**
     * Set up before each test
     */
    protected function setUp()
    {
        // Create a test user
        $user = [
            'email' => 'room.test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => 'Room',
            'last_name' => 'Test',
            'role' => 'user'
        ];
        
        // Insert the user directly into the database
        $this->userId = $this->createTestUser($user);
        
        // Create a test company
        $company = [
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'user_id' => $this->userId,
            'phone' => '555-1234',
            'address' => '123 Test St'
        ];
        
        $this->companyId = $this->createTestCompany($company);
        
        // Login to get access token
        $response = $this->runApp('POST', '/api/login', [
            'email' => 'room.test@example.com',
            'password' => 'password123'
        ]);
        
        $body = json_decode((string)$response->getBody(), true);
        $this->accessToken = $body['access_token'];
    }
    
    /**
     * Test creating a new room
     */
    public function testCreateRoom()
    {
        // Create a new room
        $room = [
            'company_id' => $this->companyId,
            'name' => 'Test Room',
            'capacity' => 10,
            'description' => 'A test meeting room'
        ];
        
        $response = $this->runApp('POST', '/api/rooms', $room, [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken
        ]);
        
        // Verify response
        $this->assertEquals(201, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Test Room', $body['room']['name']);
        $this->assertEquals(10, $body['room']['capacity']);
        
        return $body['room']['id'];
    }
    
    /**
     * Test getting rooms for a company
     * 
     * @depends testCreateRoom
     */
    public function testGetRoomsByCompany($roomId)
    {
        $response = $this->runApp('GET', "/api/companies/{$this->companyId}/rooms", null, [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken
        ]);
        
        // Verify response
        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertGreaterThan(0, count($body));
        
        // Check that our room is in the list
        $found = false;
        foreach ($body as $room) {
            if ($room['id'] == $roomId) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
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
    
    /**
     * Helper method to create a test company
     */
    private function createTestCompany(array $companyData)
    {
        // Use the Company model to create a company
        $company = \Company::create($companyData);
        return $company->id;
    }
}
