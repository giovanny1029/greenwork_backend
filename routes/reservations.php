<?php

// Import middleware classes
require_once __DIR__ . '/../src/AuthMiddleware.php';
require_once __DIR__ . '/../src/RoleBasedAuthMiddleware.php';

$app->group('/api', function () use ($app){    // GET: Obtener todas las reservas
    $app->get('/reservations', function ($request, $response) {
        $this->logger->info("Obteniendo todas las reservas");

        $data = Reservation::with('room')->get();
        return $this->response->withJson($data, 200);
    });
    $app->get('/reservations/{id}', function($request, $response, $args){
        $this->logger->info("Obteniendo reserva por ID");        $data = Reservation::with('room')->find($args['id']);
        if (!$data) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Reserva no encontrada'
            ], 404);
        }
        return $this->response->withJson($data, 200);
    });

    $app->get('/users/{id}/reservations', function($request, $response, $args){
        $this->logger->info("Obteniendo reservas por ID de usuario");        $data = Reservation::with('room')->where('user_id', $args['id'])->get();
        return $this->response->withJson($data, 200);
    });

    $app->get('/rooms/{id}/reservations', function($request, $response, $args){
        $this->logger->info("Obteniendo reservas por ID de sala");        $data = Reservation::with('room')->where('room_id', $args['id'])->get();
        return $this->response->withJson($data, 200);
    });

    $app->post('/reservations', function ($request, $response) {
        $this->logger->info("Creando nueva reserva");

        $reservation = $request->getParsedBody();
        
        $requiredFields = ['user_id', 'room_id', 'date', 'start_time', 'end_time'];
        foreach ($requiredFields as $field) {
            if (!isset($reservation[$field]) || empty($reservation[$field])) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => "El campo '$field' es requerido"
                ], 400);
            }
        }

        $user = User::find($reservation['user_id']);
        if (!$user) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'El usuario proporcionado no existe'
            ], 400);
        }

        $room = Room::find($reservation['room_id']);
        if (!$room) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'La sala proporcionada no existe'
            ], 400);
        }

        $date = $reservation['date'];
        $startTime = $reservation['start_time'];
        $endTime = $reservation['end_time'];
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Formato de fecha inválido. Use YYYY-MM-DD'
            ], 400);
        }

        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $endTime)) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Formato de hora inválido. Use HH:MM:SS'
            ], 400);
        }

        if ($startTime >= $endTime) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'La hora de inicio debe ser anterior a la hora de fin'
            ], 400);
        }
        $conflictingReservations = Reservation::where('room_id', $reservation['room_id'])
            ->where('date', $date)
            ->where(function($query) use ($startTime, $endTime) {
                $query->where(function($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            })
            ->get();
        
        if ($conflictingReservations->count() > 0) {
            $conflictTimes = $conflictingReservations->map(function($res) {
                return $res->start_time . ' - ' . $res->end_time;
            })->implode(', ');
            
            return $this->response->withJson([
                'error' => true,
                'message' => 'La sala ya está reservada en el horario seleccionado. Horarios ocupados: ' . $conflictTimes
            ], 409);
        }
          try {
            $reservationData = [
                'user_id' => $reservation['user_id'],
                'room_id' => $reservation['room_id'],
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => isset($reservation['status']) ? $reservation['status'] : 'confirmed'
            ];
            
            if (isset($reservation['total_price'])) {
                $reservationData['total_price'] = $reservation['total_price'];
            }
            
            if (isset($reservation['payment_status'])) {
                $reservationData['payment_status'] = $reservation['payment_status'];
            }
            
            if (isset($reservation['payment_method'])) {
                $reservationData['payment_method'] = $reservation['payment_method'];
            }
            
            if (isset($reservation['card_last_digits'])) {
                $reservationData['card_last_digits'] = $reservation['card_last_digits'];
            }
            
            $data = Reservation::create($reservationData);
            return $this->response->withJson([
                'success' => true,
                'message' => 'Reserva creada con éxito',
                'reservation' => $data
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error("Error al crear reserva: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Error al crear la reserva: ' . $e->getMessage()
            ], 500);
        }
    });
    $app->put('/reservations/{id}', function ($request, $response, $args) {
        $this->logger->info("Actualizando reserva");

        $existingReservation = Reservation::find($args['id']);
        if (!$existingReservation) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        $reservation = $request->getParsedBody();
        $updateData = [];
        
        if (isset($reservation['date'])) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reservation['date'])) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'Formato de fecha inválido. Use YYYY-MM-DD'
                ], 400);
            }
            $updateData['date'] = $reservation['date'];
        } else {
            $reservation['date'] = $existingReservation->date;
        }
        
        if (isset($reservation['start_time'])) {
            if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $reservation['start_time'])) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'Formato de hora de inicio inválido. Use HH:MM:SS'
                ], 400);
            }
            $updateData['start_time'] = $reservation['start_time'];
        } else {
            $reservation['start_time'] = $existingReservation->start_time;
        }
        
        if (isset($reservation['end_time'])) {
            if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $reservation['end_time'])) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'Formato de hora de fin inválido. Use HH:MM:SS'
                ], 400);
            }
            $updateData['end_time'] = $reservation['end_time'];
        } else {
            $reservation['end_time'] = $existingReservation->end_time;
        }
        
        if ($reservation['start_time'] >= $reservation['end_time']) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'La hora de inicio debe ser anterior a la hora de fin'
            ], 400);
        }
        
        if (isset($updateData['date']) || isset($updateData['start_time']) || isset($updateData['end_time'])) {
            $conflictingReservations = Reservation::where('room_id', $existingReservation->room_id)
                ->where('id', '!=', $args['id'])
                ->where('date', $reservation['date'])
                ->where(function($query) use ($reservation) {
                    $query->where(function($q) use ($reservation) {
                        $q->where('start_time', '<', $reservation['end_time'])
                          ->where('end_time', '>', $reservation['start_time']);
                    });
                })
                ->count();
            
            if ($conflictingReservations > 0) {
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'La sala ya está reservada en el horario seleccionado'
                ], 409);
            }
        }
        
        if (isset($reservation['status'])) {
            $updateData['status'] = $reservation['status'];
        }
          try {
            $data = Reservation::where('id', $args['id'])->update($updateData);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Reserva actualizada con éxito'
            ], 200);
        } catch (\Exception $e) {            $this->logger->error("Error al actualizar reserva: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Error al actualizar la reserva: ' . $e->getMessage()
            ], 500);
        }
    });
    $app->put('/reservations/{id}/cancel', function ($request, $response, $args) {
        $this->logger->info("Cancelando reserva");

        $existingReservation = Reservation::find($args['id']);
        if (!$existingReservation) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        try {
            $updateData = [
                'status' => 'cancelled'
            ];
            
            $data = Reservation::where('id', $args['id'])->update($updateData);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Reserva cancelada con éxito'
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error("Error al cancelar reserva: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Error al cancelar la reserva: ' . $e->getMessage()
            ], 500);
        }
    });
    $app->delete('/reservations/{id}', function ($request, $response, $args) {
        $this->logger->info("Eliminando reserva");

        $existingReservation = Reservation::find($args['id']);
        if (!$existingReservation) {
            return $this->response->withJson([
                'error' => true, 
                'message' => 'Reserva no encontrada'
            ], 404);
        }        try {
            $data = Reservation::destroy($args['id']);
            return $this->response->withJson([
                'success' => true,
                'message' => 'Reserva cancelada con éxito'
            ], 200);
        } catch (\Exception $e) {            $this->logger->error("Error al eliminar reserva: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Error al cancelar la reserva: ' . $e->getMessage()
            ], 500);
        }
    });
});
