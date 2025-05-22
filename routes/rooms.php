<?php

// Import middleware classes
require_once __DIR__ . '/../src/AuthMiddleware.php';
require_once __DIR__ . '/../src/RoleBasedAuthMiddleware.php';

$app->group('/api', function () use ($app){
    $app->get('/rooms', function ($request, $response) {
        $this->logger->info("Obteniendo todas las salas");

        $data = Room::all();
        return $this->response->withJson($data, 200);
    });

    $app->get('/rooms/[{id}]', function($request, $response, $args){
        $this->logger->info("Obteniendo sala por ID");

        $data = Room::find($args['id']);
        if (!$data) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Sala no encontrada'
            ], 404);
        }
        return $this->response->withJson($data, 200);
    });

    $app->get('/companies/{id}/rooms', function($request, $response, $args){
        $this->logger->info("Obteniendo salas por ID de empresa");

        $company = Company::find($args['id']);
        if (!$company) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        $data = Room::where('company_id', $args['id'])->get();
        return $this->response->withJson($data, 200);
    });

   
    $app->post('/rooms', function ($request, $response) {
        $this->logger->info("Creando nueva sala");

        $room = $request->getParsedBody();
        
        $requiredFields = ['company_id', 'name', 'capacity'];
        foreach ($requiredFields as $field) {
            if (!isset($room[$field]) || empty($room[$field])) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => "El campo '$field' es requerido"
                ], 400);
            }
        }

        $company = Company::find($room['company_id']);
        if (!$company) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'La empresa proporcionada no existe'
            ], 400);
        }
          try {
            $data = Room::create([
                'company_id' => $room['company_id'],
                'name' => $room['name'],
                'capacity' => $room['capacity'],
                'status' => isset($room['status']) ? $room['status'] : 'available',
                'description' => isset($room['description']) ? $room['description'] : null,
                'price' => isset($room['price']) ? $room['price'] : null
            ]);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Sala creada con éxito',
                'room' => $data
            ], 201);
        } catch (Exception $e) {
            $this->logger->error("Error al crear sala: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Error al crear la sala'
            ], 500);
        }
    });

    $app->put('/rooms/[{id}]', function ($request, $response, $args) {
        $this->logger->info("Actualizando sala");

        $existingRoom = Room::find($args['id']);
        if (!$existingRoom) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'Sala no encontrada'
            ], 404);
        }

        $room = $request->getParsedBody();
        $updateData = [];
        
        if (isset($room['name'])) {
            $updateData['name'] = $room['name'];
        }
        
        if (isset($room['capacity'])) {
            $updateData['capacity'] = $room['capacity'];
        }
        
        if (isset($room['status'])) {
            $updateData['status'] = $room['status'];
        }
          if (isset($room['description'])) {
            $updateData['description'] = $room['description'];
        }
        
        if (isset($room['price'])) {
            $updateData['price'] = $room['price'];
        }
        
        
        try {
            $data = Room::where('id', $args['id'])->update($updateData);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Sala actualizada con éxito'
            ], 200);
        } catch (Exception $e) {
            $this->logger->error("Error al actualizar sala: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Error al actualizar la sala'
            ], 500);
        }
    });

    $app->delete('/rooms/[{id}]', function ($request, $response, $args) {
        $this->logger->info("Eliminando sala");

        $existingRoom = Room::find($args['id']);
        if (!$existingRoom) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'Sala no encontrada'
            ], 404);
        }

        try {
            $data = Room::destroy($args['id']);
            return $this->response->withJson([
                'success' => true,
                'message' => 'Sala eliminada con éxito'
            ], 200);
        } catch (Exception $e) {
            $this->logger->error("Error al eliminar sala: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Error al eliminar la sala'
            ], 500);
        }
    });
});
