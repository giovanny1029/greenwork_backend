<?php
// filepath: c:\Users\giova\Desktop\Pruebapedro\greenwork_backend\routes\images.php

require_once __DIR__ . '/../src/AuthMiddleware.php';
require_once __DIR__ . '/../src/RoleBasedAuthMiddleware.php';
require_once __DIR__ . '/../src/FormDataHandler.php';

$app->group('/api', function () use ($app) {
    
    /**
     * @route GET /api/images
     * Obtiene todas las imágenes
     * Requiere autenticación
     */
    $app->get('/images', function ($request, $response) {
        $this->logger->info("Obteniendo todas las imágenes");

        $allImages = Image::all();
        
        // Crear un array para la respuesta con imagescol explícitamente incluido
        $data = [];
        foreach ($allImages as $img) {
            $data[] = [
                'id_image' => $img->id_image,
                'name' => $img->name,
                'imagescol' => $img->imagescol
            ];
        }
        
        return $this->response->withJson($data, 200);
    });

    /**
     * @route GET /api/images/{name}
     * Obtiene una imagen por nombre
     * Requiere autenticación
     */
    $app->get('/images/[{name}]', function($request, $response, $args) {
        $this->logger->info("Obteniendo imagen por nombre: " . $args['name']);

        $data = Image::where('name', $args['name'])->first();
        if (!$data) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Imagen no encontrada'
            ], 404);
        }
        
        return $this->response->withJson([
            'id_image' => $data->id_image,
            'name' => $data->name,
            'imagescol' => $data->imagescol
        ], 200);
    });

    /**
     * @route POST /api/images
     * Crea una nueva imagen
     * Requiere autenticación
     */    
    $app->post('/images', function($request, $response) {
        // Log
        $this->logger->info("Creando nueva imagen");

        $data = $request->getParsedBody();
        $this->logger->info("Datos recibidos en la petición POST:", [
            'data_params' => print_r($data, true), 
            'has_name' => isset($data['name']) ? 'true' : 'false'
        ]);
          
        $uploadedFiles = $request->getUploadedFiles();
        $this->logger->info("Archivos recibidos en la petición POST:", [
            'has_image' => isset($uploadedFiles['image']) ? 'true' : 'false',
            'file_count' => count($uploadedFiles)
        ]);
        
        $imageData = null;
        $imageName = null;
        
        if (isset($uploadedFiles['image']) && $uploadedFiles['image']->getError() === UPLOAD_ERR_OK) {
            $uploadedFile = $uploadedFiles['image'];
            $this->logger->info("Archivo recibido correctamente con tamaño: " . $uploadedFile->getSize());
            
            if (!isset($data['name'])) {
                $imageName = $uploadedFile->getClientFilename();
                $this->logger->info("Usando nombre del archivo: " . $imageName);
            }
            $imageStream = $uploadedFile->getStream();
            $imageData = $imageStream->getContents();
            $this->logger->info("Datos de imagen obtenidos con longitud: " . strlen($imageData));
        } else {
            if (isset($uploadedFiles['image'])) {
                $error = $uploadedFiles['image']->getError();
                $this->logger->error("Error al recibir el archivo: código " . $error);
            } else {
                $this->logger->error("No se recibió ningún archivo 'image' en la petición");
            }
        }
        
        if (isset($data['name'])) {
            $imageName = $data['name'];
        }
        
        if ($imageName === null) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'No se proporcionó ninguna imagen o nombre'
            ], 400);
        }
        
        try {
            $image = new Image();
            
            if ($imageData !== null) {
                $image->imagescol = base64_encode($imageData);
                $this->logger->info("Imagen convertida a base64 con longitud: " . strlen($image->imagescol));
            } else {
                $image->imagescol = null;
                $this->logger->warning("No se recibieron datos de imagen, guardando con imagescol = null");
            }
            $image->name = $imageName;
            
            $result = $image->save();
            $this->logger->info("Resultado del guardado: " . ($result ? 'éxito' : 'fallo'));
            
            $savedImage = Image::where('name', $imageName)->first();
            $this->logger->info("Imagen recuperada después de guardar:", [
                'id' => $savedImage->id_image ?? 'null',
                'name' => $savedImage->name ?? 'null',
                'has_image_data' => !empty($savedImage->imagescol) ? 'true' : 'false',
                'image_data_length' => strlen($savedImage->imagescol ?? '')
            ]);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Imagen creada correctamente',
                'image' => [
                    'id_image' => $image->id_image,
                    'name' => $image->name,
                    'imagescol' => $image->imagescol // Incluir explícitamente el campo imagescol en la respuesta
                ]
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error("Error al crear imagen: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Error al crear la imagen: ' . $e->getMessage()
            ], 500);
        }
    });

    /**
     * @route PUT /api/images/{id}
     * Actualiza una imagen existente
     * Requiere autenticación
     */    
    $app->put('/images/[{id}]', function($request, $response, $args) {
        $this->logger->info("Actualizando imagen con ID: " . $args['id']);
        $this->logger->info("Método HTTP: " . $request->getMethod());
        $this->logger->info("Content-Type: " . $request->getHeaderLine('Content-Type'));

        $image = Image::find($args['id']);
        if (!$image) {
            $this->logger->error("Imagen no encontrada con ID: " . $args['id']);
            return $this->response->withJson([
                'error' => true,
                'message' => 'Imagen no encontrada'
            ], 404);
        }

        $data = $request->getParsedBody();
        $this->logger->info("Datos recibidos en la petición PUT:", [
            'data_params' => print_r($data, true), 
            'has_name' => isset($data['name']) ? 'true' : 'false',
            'content_type' => $request->getHeaderLine('Content-Type')
        ]);
          
        $uploadedFiles = $request->getUploadedFiles();
        $this->logger->info("Archivos recibidos en la petición PUT:", [
            'has_image' => isset($uploadedFiles['image']) ? 'true' : 'false',
            'file_count' => count($uploadedFiles)
        ]);
        
        $alternativeFiles = FormDataHandler::processFormData($request);
        $this->logger->info("Archivos obtenidos con método alternativo:", [
            'has_image' => isset($alternativeFiles['image']) ? 'true' : 'false',
            'file_count' => count($alternativeFiles),
            'file_details' => print_r($alternativeFiles, true)
        ]);
          
        if (isset($uploadedFiles['image']) && $uploadedFiles['image']->getError() === UPLOAD_ERR_OK) {
            $uploadedFile = $uploadedFiles['image'];
            $this->logger->info("Archivo recibido correctamente en PUT con tamaño: " . $uploadedFile->getSize(), [
                'mediaType' => $uploadedFile->getClientMediaType(),
                'filename' => $uploadedFile->getClientFilename()
            ]);
            
            $imageStream = $uploadedFile->getStream();
            $imageData = $imageStream->getContents();
            $image->imagescol = base64_encode($imageData);
            $this->logger->info("Imagen convertida a base64 con longitud: " . strlen($image->imagescol));
        } else if (isset($alternativeFiles['image']) && $alternativeFiles['image']['error'] === UPLOAD_ERR_OK) {
            $this->logger->info("Usando método alternativo para obtener la imagen");
            $imageData = FormDataHandler::getFileContents($alternativeFiles['image']);
            if ($imageData !== null) {
                $image->imagescol = base64_encode($imageData);
                $this->logger->info("Imagen obtenida con método alternativo, longitud: " . strlen($image->imagescol));
            } else {
                $this->logger->error("No se pudo leer el contenido del archivo desde el método alternativo");
            }
        } else {
            // Ningún método de archivo funcionó
            if (isset($uploadedFiles['image'])) {
                $error = $uploadedFiles['image']->getError();
                $this->logger->error("Error al recibir el archivo en PUT: código " . $error);
                $this->logger->error("Detalles de error: " . $this->getUploadErrorDescription($error));
            } else {
                $this->logger->error("No se recibió ningún archivo 'image' en la petición PUT");
                $this->logger->info("Archivos disponibles en la petición PUT:", [
                    'keys' => array_keys($uploadedFiles),
                    'count' => count($uploadedFiles)
                ]);
            }
            
            if (isset($data['imageData']) && !empty($data['imageData'])) {
                $this->logger->info("Se recibieron datos de imagen en formato base64, longitud: " . strlen($data['imageData']));
                $image->imagescol = $data['imageData']; // Guardar directamente, no codificar de nuevo
                $this->logger->info("Datos base64 asignados correctamente");
            } else {
                $this->logger->warn("No se recibió ningún archivo ni datos de imagen en la petición PUT");
                return $this->response->withJson([
                    'error' => true,
                    'message' => 'No se proporcionó ninguna imagen para actualizar'
                ], 400);
            }
        }
        
        // Actualizar nombre si se proporcionó
        if (isset($data['name'])) {
            $image->name = $data['name'];
            $this->logger->info("Nombre de imagen actualizado a: " . $data['name']);
        }
        
        try {
            $result = $image->save();
            $this->logger->info("Resultado del guardado: " . ($result ? 'éxito' : 'fallo'));
            
            // Verificar que la imagen se guardó correctamente
            $savedImage = Image::find($args['id']);
            $this->logger->info("Imagen recuperada después de guardar:", [
                'id' => $savedImage->id_image,
                'name' => $savedImage->name,
                'has_image_data' => !empty($savedImage->imagescol) ? 'true' : 'false',
                'image_data_length' => strlen($savedImage->imagescol ?? ''),
                'image_data_first_100_chars' => substr($savedImage->imagescol ?? '', 0, 100)
            ]);
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Imagen actualizada correctamente',
                'image' => [
                    'id_image' => $savedImage->id_image,
                    'name' => $savedImage->name,
                    'imagescol' => $savedImage->imagescol // Incluir explícitamente el campo imagescol en la respuesta
                ]
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error("Error al actualizar imagen: " . $e->getMessage(), [
                'exception_trace' => $e->getTraceAsString()
            ]);
            return $this->response->withJson([
                'error' => true,
                'message' => 'Error al actualizar la imagen: ' . $e->getMessage()
            ], 500);
        }
    });

    /**
     * @route DELETE /api/images/{id}
     * Elimina una imagen
     * Requiere autenticación
     */
    $app->delete('/images/[{id}]', function($request, $response, $args) {
        // Log
        $this->logger->info("Eliminando imagen con ID: " . $args['id']);

        // Buscar la imagen
        $image = Image::find($args['id']);
        if (!$image) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Imagen no encontrada'
            ], 404);
        }

        try {
            $image->delete();
            
            return $this->response->withJson([
                'success' => true,
                'message' => 'Imagen eliminada correctamente'
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error("Error al eliminar imagen: " . $e->getMessage());
            return $this->response->withJson([
                'error' => true,
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ], 500);
        }
    });

    /**
     * @route GET /api/images/data/{id}
     * Obtiene los datos binarios de una imagen por su ID
     * No requiere autenticación para permitir acceso desde navegadores
     */
    $app->get('/images/data/[{id}]', function($request, $response, $args) {
        // Log
        $this->logger->info("Obteniendo datos binarios de imagen con ID: " . $args['id']);

        $data = Image::find($args['id']);
        if (!$data) {
            return $this->response->withJson([
                'error' => true,
                'message' => 'Imagen no encontrada'
            ], 404);
        }
        
        // Si los datos están en base64, asegurarnos que permanecen así
        // Si son datos binarios, convertirlos a base64
        $imageData = $data->imagescol;
        
        // Verificar si ya es una cadena base64 válida
        if (!is_string($imageData) || base64_encode(base64_decode($imageData, true)) !== $imageData) {
            // No es una cadena base64 válida, probablemente son datos binarios
            // Los convertimos a base64
            $imageData = base64_encode($imageData);
        }
        
        // Detectar el tipo de imagen decodificando una pequeña muestra
        try {
            $decodedSample = base64_decode(substr($imageData, 0, 100), true);
            if ($decodedSample) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_buffer($finfo, $decodedSample);
                finfo_close($finfo);
                
                if (!$mime) {
                    $mime = 'image/jpeg'; // Tipo predeterminado
                }
            } else {
                $mime = 'image/jpeg'; // No pudimos decodificar, usar tipo predeterminado
            }
        } catch (\Exception $e) {
            $mime = 'image/jpeg'; // Error al procesar, usar tipo predeterminado
        }
        
        // Si no se pudo detectar el MIME, usar un tipo genérico
        if (!$mime) {
            $mime = 'image/jpeg';
        }
        
        // Devolver la imagen como URL de datos base64
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withJson([
                'success' => true,
                'data' => 'data:' . $mime . ';base64,' . $imageData,
                'mime' => $mime,
                'name' => $data->name,
                'id' => $data->id_image
            ]);
    });

})->add(new AuthMiddleware($app->getContainer())); // Aplicar middleware de autenticación a todas las rutas de imágenes

/**
 * Función auxiliar para obtener una descripción legible de los errores de subida de archivos
 */
function getUploadErrorDescription($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize)';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el formulario (MAX_FILE_SIZE)';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo fue subido parcialmente';
        case UPLOAD_ERR_NO_FILE:
            return 'No se subió ningún archivo';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta la carpeta temporal en el servidor';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Error al escribir el archivo en disco';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP detuvo la subida del archivo';
        default:
            return 'Error desconocido (código: ' . $errorCode . ')';
    }
}
