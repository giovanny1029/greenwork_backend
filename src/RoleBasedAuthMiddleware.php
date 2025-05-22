<?php
// filepath: c:\dev\giova\backend\src\RoleBasedAuthMiddleware.php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class RoleBasedAuthMiddleware
{
    private $container;
    private $allowedRoles;

    /**
     * Constructor
     *
     * @param \Slim\Container $container DI Container
     * @param array $allowedRoles Array of roles allowed to access the resource
     */
    public function __construct($container, array $allowedRoles)
    {
        $this->container = $container;
        $this->allowedRoles = $allowedRoles;
    }

    /**
     * Role-based authorization middleware
     *
     * @param Request $request PSR7 request
     * @param Response $response PSR7 response
     * @param callable $next Next middleware
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        // Get user from request attributes (set by AuthMiddleware)
        $user = $request->getAttribute('user');

        if (!$user || !isset($user['role'])) {
            return $response->withJson([
                'error' => true,
                'message' => 'Unauthorized: User not authenticated'
            ], 401);
        }

        // Check if user role is allowed
        if (!in_array($user['role'], $this->allowedRoles)) {
            return $response->withJson([
                'error' => true,
                'message' => 'Forbidden: Insufficient permissions'
            ], 403);
        }

        // User has required role, proceed
        return $next($request, $response);
    }
}
