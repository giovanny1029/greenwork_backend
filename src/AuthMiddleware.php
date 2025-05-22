<?php
// filepath: c:\dev\giova\backend\src\AuthMiddleware.php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class AuthMiddleware
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Authentication middleware
     *
     * @param Request $request PSR7 request
     * @param Response $response PSR7 response
     * @param callable $next Next middleware
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        // Get JWT from header
        $token = $request->getHeaderLine('Authorization');

        if (!$token) {
            return $response->withJson([
                'error' => true,
                'message' => 'Authorization token required'
            ], 401);
        }

        // Remove "Bearer " from token string
        $token = str_replace('Bearer ', '', $token);

        try {
            // Verify token and get user data
            $userData = $this->verifyToken($token);

            // Add user data to request for use in routes
            $request = $request->withAttribute('user', $userData);

            // Call next middleware
            $response = $next($request, $response);
            return $response;
        } catch (\Exception $e) {
            return $response->withJson([
                'error' => true,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Verify JWT token and extract user data
     *
     * @param string $token JWT token
     * @return array User data from token
     * @throws \Exception If token is invalid
     */    private function verifyToken($token)
    {
        // Get secret key from environment or config
        $secret = getenv('JWT_SECRET') ?: 'your-secret-key';

        // Decode token using Firebase JWT library
        try {
            // Use the updated JWT decode method syntax
            $payload = \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key($secret, 'HS256')
            );

            // Check if token is expired
            if (isset($payload->exp) && $payload->exp < time()) {
                throw new \Exception('Token has expired');
            }

            // Get user data
            $userData = (array)$payload->data;
            
            // Verify that user still exists in database
            $user = \User::find($userData['id']);
            if (!$user) {
                throw new \Exception('User not found');
            }

            return $userData;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }
}
