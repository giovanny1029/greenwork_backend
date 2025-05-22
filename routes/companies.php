<?php

// Import middleware classes
require_once __DIR__ . '/../src/AuthMiddleware.php';
require_once __DIR__ . '/../src/RoleBasedAuthMiddleware.php';

$app->group('/api', function () use ($app){// GET: Obtener todas las empresas
    $app->get('/companies', function ($request, $response) {
        // Log
        $this->logger->info("Obteniendo todas las empresas");

        $data = Company::all();
        return $this->response->withJson($data, 200);
    })->add(new AuthMiddleware($app->getContainer()));    // GET: Obtener una empresa por ID
    $app->get('/companies/[{id}]', function($request, $response, $args){
        // Log
        $this->logger->info("Obteniendo empresa por ID");

        $data = Company::find($args['id']);
        if (!$data) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Empresa no encontrada'
            ], 404);
        }
        return $this->response->withJson($data, 200);
    })->add(new AuthMiddleware($app->getContainer()));    // GET: Obtener empresas por user_id
    $app->get('/users/{id}/companies', function($request, $response, $args){
        // Log
        $this->logger->info("Obteniendo empresas por ID de usuario");
        
        // Get authenticated user data
        $authUser = $request->getAttribute('user');
        
        // Check if the user is requesting their own companies or if they're an admin
        if ($authUser['role'] !== 'admin' && $authUser['id'] != $args['id']) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'No tienes permiso para ver estas empresas'
            ], 403);
        }        $data = Company::where('user_id', $args['id'])->get();
        return $this->response->withJson($data, 200);
    })->add(new AuthMiddleware($app->getContainer()));

    // POST: Crear una nueva empresa
    $app->post('/companies', function ($request, $response) {
        // Log
        $this->logger->info("Creando nueva empresa");

        $company = $request->getParsedBody();
        
        // Validar campos requeridos
        $requiredFields = ['user_id', 'name', 'email'];
        foreach ($requiredFields as $field) {
            if (!isset($company[$field]) || empty($company[$field])) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => "El campo '$field' es requerido"
                ], 400);
            }
        }
        
        // Verificar si el email ya está registrado
        $existingCompany = Company::where('email', $company['email'])->first();
        if ($existingCompany) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'El email ya está registrado para otra empresa'
            ], 409);
        }

        // Verificar si el usuario existe
        $user = User::find($company['user_id']);
        if (!$user) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'El usuario proporcionado no existe'
            ], 400);
        }
        
        try {
            $data = Company::create([
                'user_id' => $company['user_id'],
                'name' => $company['name'],
                'email' => $company['email'],
                'phone' => isset($company['phone']) ? $company['phone'] : null,
                'address' => isset($company['address']) ? $company['address'] : null
            ]);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Empresa creada con éxito',
                'company' => $data
            ], 201);
        } catch (Exception $e) {
            $this->logger->error("Error al crear empresa: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,                'message' => 'Error al crear la empresa'
            ], 500);
        }
    })->add(new AuthMiddleware($app->getContainer()));    // PUT: Actualizar una empresa
    $app->put('/companies/[{id}]', function ($request, $response, $args) {
        // Log
        $this->logger->info("Actualizando empresa");
        
        // Get authenticated user data
        $authUser = $request->getAttribute('user');

        // Verificar si la empresa existe
        $existingCompany = Company::find($args['id']);
        if (!$existingCompany) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'Empresa no encontrada'
            ], 404);
        }
        
        // Check if user is owner or admin
        if ($authUser['role'] !== 'admin' && $existingCompany->user_id != $authUser['id']) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'No tienes permiso para modificar esta empresa'
            ], 403);
        }

        $company = $request->getParsedBody();
        $updateData = [];
        
        // Actualizar solo los campos proporcionados
        if (isset($company['name']) && !empty($company['name'])) {
            $updateData['name'] = $company['name'];
        }
        
        if (isset($company['email']) && !empty($company['email'])) {
            // Verificar que el email no esté en uso por otra empresa
            $emailCheck = Company::where('email', $company['email'])
                ->where('id', '!=', $args['id'])
                ->first();
            
            if ($emailCheck) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'El email ya está en uso por otra empresa'
                ], 409);
            }
            
            $updateData['email'] = $company['email'];
        }
        
        if (isset($company['phone'])) {
            $updateData['phone'] = $company['phone'];
        }
        
        if (isset($company['address'])) {
            $updateData['address'] = $company['address'];
        }
        
        // No permitimos cambiar el user_id para evitar problemas de propiedad
        
        try {
            $data = Company::where('id', $args['id'])->update($updateData);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Empresa actualizada con éxito'
            ], 200);
        } catch (Exception $e) {
            $this->logger->error("Error al actualizar empresa: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,                'message' => 'Error al actualizar la empresa'
            ], 500);
        }
    })->add(new AuthMiddleware($app->getContainer()));    // DELETE: Eliminar una empresa
    $app->delete('/companies/[{id}]', function ($request, $response, $args) {
        // Log
        $this->logger->info("Eliminando empresa");
        
        // Get authenticated user data
        $authUser = $request->getAttribute('user');

        // Verificar si la empresa existe
        $existingCompany = Company::find($args['id']);
        if (!$existingCompany) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'Empresa no encontrada'
            ], 404);
        }
        
        // Check if user is owner or admin
        if ($authUser['role'] !== 'admin' && $existingCompany->user_id != $authUser['id']) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'No tienes permiso para eliminar esta empresa'
            ], 403);
        }

        try {
            $data = Company::destroy($args['id']);
            return $this->response->withJson([
                'success' => true,
                'message' => 'Empresa eliminada con éxito'
            ], 200);
        } catch (Exception $e) {
            $this->logger->error("Error al eliminar empresa: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,                'message' => 'Error al eliminar la empresa'
            ], 500);
        }
    })->add(new AuthMiddleware($app->getContainer()));
});
