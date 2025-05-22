<?php
use Firebase\JWT\JWT;

// Import middleware classes
require_once __DIR__ . '/../src/AuthMiddleware.php';
require_once __DIR__ . '/../src/RoleBasedAuthMiddleware.php';

// Check JWT class availability 
if (!class_exists('\\Firebase\\JWT\\JWT')) {
    error_log('Firebase JWT class not available');
}

$app->group('/api', function () use ($app){
    $app->get('/users', function ($request, $response) {
        // put log message
        $this->logger->info("getting all users");

        $data = User::all();
        return $this->response->withJson($data, 200);
    })->add(new AuthMiddleware($app->getContainer()));    $app->get('/users/{id}', function($request, $response, $args){
        // put log message
        $this->logger->info("getting user by id");

        $data = User::find($args['id']);
        
        if (!$data) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'User not found'
            ], 404);
        }
        
        // Remove password from response
        unset($data->password);
        
        return $this->response->withJson($data, 200);
    })->add(new AuthMiddleware($app->getContainer()));
    $app->post('/users', function ($request, $response) {
        // put log message
        $this->logger->info("saving user - admin only");

        $user = $request->getParsedBody();
        
        // Validate required fields
        $requiredFields = ['email', 'password', 'first_name'];
        foreach ($requiredFields as $field) {
            if (!isset($user[$field]) || empty($user[$field])) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => "Field '$field' is required"
                ], 400);
            }
        }
        
        // Check if email already exists
        $existingUser = User::where('email', $user['email'])->first();
        if ($existingUser) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Email already registered'
            ], 409); // 409 Conflict
        }
        
        try {
            $data = User::create([
                'first_name' => $user['first_name'],
                'last_name' => isset($user['last_name']) ? $user['last_name'] : '',
                'email' => $user['email'],
                'password' => password_hash($user['password'], PASSWORD_DEFAULT),
                'role' => isset($user['role']) ? $user['role'] : 'user'
            ]);
            
            // Remove password from response
            unset($data->password);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $data
            ], 201); // 201 Created
        } catch (Exception $e) {
            $this->logger->error("User creation error: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,                'message' => 'User creation failed'
            ], 500);
        }
    })->add(new RoleBasedAuthMiddleware($app->getContainer(), ['admin']))
      ->add(new AuthMiddleware($app->getContainer()));
    $app->post('/register', function ($request, $response) {
        // put log message
        $this->logger->info("user registration attempt");

        try {
            $user = $request->getParsedBody();
            $this->logger->info("Received registration data: " . json_encode($user));
            
            // Validate required fields
            $requiredFields = ['email', 'password', 'first_name', 'last_name'];
            foreach ($requiredFields as $field) {
                if (!isset($user[$field]) || empty($user[$field])) {
                    $this->logger->error("Missing required field: " . $field);
                    return $this->response->withJson([
                        'error' => true,
                        'message' => "Field '$field' is required"
                    ], 400);
                }
            }
            
            // Check if email already exists
            $existingUser = User::where('email', $user['email'])->first();
            if ($existingUser) {
                $this->logger->info("Email already registered: " . $user['email']);
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'Email already registered'
                ], 409); // 409 Conflict
            }
            
            // Set default role if not provided
            if (!isset($user['role']) || empty($user['role'])) {
                $user['role'] = 'user'; // Default role
            }
            
            // Set last_name if provided
            $lastName = isset($user['last_name']) ? $user['last_name'] : '';
            
            // Logging model structure for debugging
            $this->logger->info("User model fillable fields: " . implode(", ", (new User())->getFillable()));
              // Debug log - create array explicitly without any company_id
            $userData = [
                'first_name' => $user['first_name'],
                'last_name' => $lastName,
                'email' => $user['email'],
                'password' => password_hash($user['password'], PASSWORD_DEFAULT),
                'role' => $user['role']
            ];
            
            $this->logger->info("Attempting to create user with data: " . json_encode($userData));
            
            // Create user with hashed password
            $data = User::create($userData);
            
            $this->logger->info("User created successfully, ID: " . $data->id);
              
            // Remove password from response
            unset($data->password);// Create token payload
            $payload = [
                'iss' => 'greenwork-api', // Issuer
                'iat' => time(), // Issued at: time when the token was generated
                'exp' => time() + (60 * 60 * 24 * 7), // Expiration: 1 week from now
                'data' => [
                    'id' => $data->id,
                    'email' => $data->email,
                    'role' => $data->role
                ]
            ];
            
            $this->logger->info("Payload created for JWT");
            
            // Get secret key from environment or config
            $secret = getenv('JWT_SECRET') ?: 'your-secret-key';
            
            // Generate JWT
            $jwt = \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
            $this->logger->info("JWT token generated successfully");
            
            // Generate refresh token
            $refreshToken = bin2hex(random_bytes(32)); // Generate a secure random string
            $this->logger->info("Refresh token generated");
            
            // Store refresh token in database with expiry time (30 days)
            $refreshTokenExpiry = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 30));
            
            Token::create([
                'user_id' => $data->id,
                'refresh_token' => $refreshToken,
                'expires_at' => $refreshTokenExpiry
            ]);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Registration successful',
                'access_token' => $jwt,
                'refresh_token' => $refreshToken,
                'user' => $data
            ], 201); // 201 Created
        } catch (Exception $e) {
            $this->logger->error("Registration error: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Registration failed'
            ], 500);
        }
    });    $app->put('/users/{id}', function ($request, $response, $args) {
        // put log message
        try {
            $this->logger->info("updating user", ['id' => isset($args['id']) ? $args['id'] : 'none']);
            
            // Get authenticated user data
            $authUser = $request->getAttribute('user');
            $this->logger->info("Auth user data", ['user' => $authUser ?? 'null']);
            
            // Log the request body for debugging
            $this->logger->info("Request body", ['body' => json_encode($request->getParsedBody())]);
        } catch(Exception $e) {
            $this->logger->error("Error in PUT /users/[{id}] initial logging: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'User update failed'
            ], 500);
        }        // Check if the user is updating their own profile or if they're an admin
        if (!isset($authUser) || !isset($authUser['role']) || ($authUser['role'] !== 'admin' && $authUser['id'] != $args['id'])) {
            $this->logger->error("Permission denied", [
                'auth_user' => $authUser ?? 'null',
                'requested_id' => $args['id'] ?? 'null'
            ]);
            return $this->response->withJson([
                'error' => true,
                'message' => 'You can only update your own profile'
            ], 403);
        }

        // Check if user exists
        $existingUser = User::find($args['id']);
        if (!$existingUser) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'User not found'
            ], 404);
        }

        $user = $request->getParsedBody();
        $updateData = [];
          // Only update fields that are provided
        if (isset($user['first_name'])) {
            $updateData['first_name'] = $user['first_name'];
        }
        
        if (isset($user['last_name'])) {
            $updateData['last_name'] = $user['last_name'];
        }
        
        if (isset($user['email'])) {
            // Check if email is already in use by another user
            $emailCheck = User::where('email', $user['email'])
                ->where('id', '!=', $args['id'])
                ->first();
            
            if ($emailCheck) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'Email already in use by another user'
                ], 409);
            }
            
            $updateData['email'] = $user['email'];
        }
        
        if (isset($user['password']) && !empty($user['password'])) {
            $updateData['password'] = password_hash($user['password'], PASSWORD_DEFAULT);
        }
          if (isset($user['role'])) {
            // Only admins can change roles
            if ($authUser['role'] === 'admin') {
                $updateData['role'] = $user['role'];
                $this->logger->info("Admin changing user role", [
                    'user_id' => $args['id'],
                    'new_role' => $user['role'],
                    'admin_id' => $authUser['id']
                ]);
            } else {
                $this->logger->warning("Non-admin attempted to change role", [
                    'user_id' => $args['id'],
                    'requested_role' => $user['role'],
                    'requester_id' => $authUser['id']
                ]);
                // Silently ignore role change attempt for non-admins
            }
        }
        
        try {
            $data = User::where('id', $args['id'])->update($updateData);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => User::find($args['id'])
            ], 200);
        } catch (Exception $e) {
            $this->logger->error("User update error: " . $e->getMessage());            return $this->response->withJson([
                'error' => true,                'message' => 'User update failed'
            ], 500);
        }
    })->add(new AuthMiddleware($app->getContainer()));    $app->post('/users/{id}/change-password', function ($request, $response, $args) {
        // put log message
        $this->logger->info("changing user password", ['id' => isset($args['id']) ? $args['id'] : 'none']);

        // Get authenticated user data
        $authUser = $request->getAttribute('user');
        
        // Check if the user is changing their own password or if they're an admin
        if (!isset($authUser) || !isset($authUser['role']) || ($authUser['role'] !== 'admin' && $authUser['id'] != $args['id'])) {
            $this->logger->error("Permission denied for password change", [
                'auth_user' => $authUser ?? 'null',
                'requested_id' => $args['id'] ?? 'null'
            ]);
            return $this->response->withJson([
                'error' => true,
                'message' => 'You can only change your own password'
            ], 403);
        }

        // Check if user exists
        $existingUser = User::find($args['id']);
        if (!$existingUser) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'User not found'
            ], 404);
        }

        $data = $request->getParsedBody();
        
        // Validate required fields
        if (!isset($data['current_password']) || empty($data['current_password']) || 
            !isset($data['new_password']) || empty($data['new_password'])) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Current password and new password are required'
            ], 400);
        }
        
        // Verify current password
        if (!password_verify($data['current_password'], $existingUser->password)) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Current password is incorrect'
            ], 401);
        }
        
        // Check if new password is strong enough (you can add your own logic here)
        if (strlen($data['new_password']) < 8) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'New password must be at least 8 characters long'
            ], 400);
        }
        
        try {
            // Update password
            $existingUser->password = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $existingUser->save();
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Password changed successfully'
            ], 200);
        } catch (Exception $e) {
            $this->logger->error("Password change error: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Password change failed'
            ], 500);
        }
    })->add(new AuthMiddleware($app->getContainer()));    $app->delete('/users/{id}', function ($request, $response, $args) {
        // put log message
        $this->logger->info("deleting user - admin only");

        // Check if user exists
        $existingUser = User::find($args['id']);
        if (!$existingUser) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'User not found'
            ], 404);
        }

        try {
            $data = User::destroy($args['id']);
            return $this->response->withJson([
                'success' => true,
                'message' => 'User deleted successfully'
            ], 200);
        } catch (Exception $e) {
            $this->logger->error("User deletion error: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,                'message' => 'User deletion failed'
            ], 500);
        }
    })->add(new RoleBasedAuthMiddleware($app->getContainer(), ['admin']))
      ->add(new AuthMiddleware($app->getContainer()));
});