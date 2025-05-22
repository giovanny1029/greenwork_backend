<?php
// filepath: c:\dev\giova\backend\routes\auth.php
use Firebase\JWT\JWT;

// Import middleware classes
require_once __DIR__ . '/../src/AuthMiddleware.php';
require_once __DIR__ . '/../src/RoleBasedAuthMiddleware.php';

// Check JWT class availability 
if (!class_exists('\\Firebase\\JWT\\JWT')) {
    error_log('Firebase JWT class not available');
}

$app->group('/api', function () use ($app) {
    /**
     * @route POST /api/login
     * Authenticates a user and provides a JWT token
     */   
    $app->post('/login', function ($request, $response) {
        $this->logger->info("User login attempt");
        $jwt = null;
        
        try {
            $input = $request->getParsedBody();
            
            // Log input data (excluding password)
            $this->logger->info("Login data received", [
                'email' => $input['email'] ?? 'not provided',
                'has_password' => isset($input['password']) ? 'yes' : 'no'
            ]);
            
            // Check required fields
            if (!isset($input['email']) || !isset($input['password'])) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'Email and password are required'
                ], 400);
            }
            
            // Get user by email
            $user = User::where('email', $input['email'])->first();
            
            // Log user lookup result
            if ($user) {
                $this->logger->info("User found", ['id' => $user->id, 'email' => $user->email]);
            } else {
                $this->logger->info("User not found", ['email' => $input['email']]);
            }
            
            // Check if user exists
            if (!$user) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'Invalid email or password'
                ], 401);
            }
              // Verify password
            if (!password_verify($input['password'], $user->password)) {
                $this->logger->info("Password verification failed", ['user_id' => $user->id]);
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'Invalid email or password'
                ], 401);
            }
            
            $this->logger->info("Password verified successfully", ['user_id' => $user->id]);
            
            // Guardamos una referencia local al usuario
            $userData = [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Exception in login route: " . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return $this->response->withJson([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
        
        // Verificamos que tenemos la información del usuario antes de continuar
        if (!isset($userData)) {
            $this->logger->error("User data not available after authentication");
            return $this->response->withJson([
                'error' => true,
                'message' => 'Authentication error'
            ], 500);
        }
        
        try {
            $this->logger->info("Starting token generation", ['user_id' => $userData['id']]);
            
            // Create token payload
            $payload = [
                'iss' => 'greenwork-api', // Issuer
                'iat' => time(), // Issued at: time when the token was generated
                'exp' => time() + (60 * 60 * 24 * 7), // Expiration: 1 week from now
                'data' => [
                    'id' => $userData['id'],
                    'email' => $userData['email'],
                    'role' => $userData['role']
                ]
            ];
            
            $this->logger->info("Token payload created", ['user_id' => $userData['id']]);
              // Get secret key from environment or config
            $secret = getenv('JWT_SECRET') ?: 'your-secret-key';
            $this->logger->info("Got JWT secret key");
              // Generate JWT
            $this->logger->info("Attempting to encode JWT");
            try {
                $jwt = JWT::encode($payload, $secret, 'HS256');
                $this->logger->info("JWT encoded successfully");
            } catch (\Exception $jwtException) {
                $this->logger->error("JWT encode exception: " . $jwtException->getMessage());
                throw $jwtException; // Re-throw para ser capturado por el catch externo
            }

              
            // Generate refresh token
            $refreshToken = bin2hex(random_bytes(32)); // Generate a secure random string
              // Store refresh token in database with expiry time (30 days)
            $refreshTokenExpiry = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 30));
            $this->logger->info("Refresh token expiry calculated", ['expires_at' => $refreshTokenExpiry]);
            
            $this->logger->info("Storing refresh token in database");
            Token::create([
                'user_id' => $userData['id'],
                'refresh_token' => $refreshToken,
                'expires_at' => $refreshTokenExpiry
            ]);
            $this->logger->info("Refresh token stored successfully");
            
            // Return tokens to client
            return $this->response->withJson([
                'success' => true,
                'message' => 'Login successful',
                'access_token' => $jwt,
                'refresh_token' => $refreshToken,
                'user' => [
                    'id' => $userData['id'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'email' => $userData['email'],
                    'role' => $userData['role']
                ]
            ]);
        } catch (Exception $e) {
            $this->logger->error("Exception in token generation: " . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return $this->response->withJson([
                'error' => true,
                'message' => 'Failed to generate token'
            ], 500);
        }
        
    });
    
    /**
     * @route POST /api/refresh
     * Refreshes JWT access token using a refresh token
     */
    $app->post('/refresh', function ($request, $response) {
        $this->logger->info("Token refresh attempt");
        
        $input = $request->getParsedBody();
        
        if (!isset($input['refresh_token']) || empty($input['refresh_token'])) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Refresh token is required'
            ], 400);
        }
        
        // Find the token in the database
        $tokenRecord = Token::where('refresh_token', $input['refresh_token'])
                           ->where('is_revoked', 0)
                           ->first();
        
        // Check if token exists and is valid
        if (!$tokenRecord || strtotime($tokenRecord->expires_at) < time()) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Invalid or expired refresh token'
            ], 401);
        }
        
        // Get user associated with token
        $user = User::find($tokenRecord->user_id);
        
        if (!$user) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'User not found'
            ], 404);
        }
        
        // Create new access token
        $payload = [
            'iss' => 'greenwork-api',
            'iat' => time(),
            'exp' => time() + (60 * 60), // Access token expires in 1 hour
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]
        ];
        
        // Get secret key from environment or config
        $secret = getenv('JWT_SECRET') ?: 'your-secret-key';
        
        // Generate new JWT
        $jwt = \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
        
        return $this->response->withJson([
            'success' => true,
            'message' => 'Token refreshed',
            'access_token' => $jwt
        ]);
    });
      /**
     * @route POST /api/logout
     * Logs out a user by revoking their refresh token
     */
    $app->post('/logout', function ($request, $response) {
        $this->logger->info("User logout attempt");
        
        $input = $request->getParsedBody();
        
        if (!isset($input['refresh_token']) || empty($input['refresh_token'])) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Refresh token is required'
            ], 400);
        }
        
        // Find and revoke the token
        $token = Token::where('refresh_token', $input['refresh_token'])->first();
        
        if ($token) {
            $token->is_revoked = 1;
            $token->save();
        }
        
        return $this->response->withJson([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    });
    
    /**
     * @route GET /api/me
     * Returns the authenticated user's data
     */
    $app->get('/me', function ($request, $response) {
        // User data was extracted from token by AuthMiddleware
        $userData = $request->getAttribute('user');
        
        // Get complete user data from database
        $user = User::find($userData['id']);
        
        if (!$user) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'User not found'
            ], 404);
        }
        
        // Return user data (without password)
        $user = $user->toArray();
        unset($user['password']);
          return $this->response->withJson([
            'success' => true,
            'user' => $user
        ]);
    })->add(new AuthMiddleware($app->getContainer()));
      // Las rutas para el restablecimiento de contraseña han sido eliminadas porque esta aplicación no enviará emails
});
