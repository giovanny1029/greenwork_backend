<?php
// Application middleware

use Tuupola\Middleware\CorsMiddleware;

// Configuración del middleware CORS de Tuupola
$app->add(new CorsMiddleware([
    "origin" => ["*"],
    "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
    "headers.allow" => ["Authorization", "Content-Type", "Accept", "Origin", "X-Requested-With"],
    "headers.expose" => ["Authorization", "Content-Type"],
    "credentials" => true,
    "cache" => 86400
]));

// Middleware para parsear contenido multipart/form-data en todas las rutas
$app->add(function ($request, $response, $next) {
    $contentType = $request->getHeaderLine('Content-Type');
    
    // Si el contenido es multipart/form-data y es una solicitud PUT
    if (strpos($contentType, 'multipart/form-data') !== false && $request->getMethod() === 'PUT') {
        $this->logger->info('Procesando solicitud PUT con multipart/form-data', [
            'Content-Type' => $contentType,
            'Method' => $request->getMethod(),
            'Uri' => $request->getUri()->getPath()
        ]);
        
        // Verificar existencia de archivos
        $uploadedFiles = $request->getUploadedFiles();
        $this->logger->info('Files recibidos en middleware:', [
            'count' => count($uploadedFiles),
            'keys' => array_keys($uploadedFiles)
        ]);
        
        // Verificar contenido del body
        $params = $request->getParsedBody();
        $this->logger->info('Body recibido en middleware:', [
            'params' => $params ? array_keys($params) : 'ninguno',
            'count' => $params ? count($params) : 0
        ]);
        
        // Si no hay archivos subidos, podemos intentar extraerlos manualmente del cuerpo de la solicitud
        if (empty($uploadedFiles) && !empty($_FILES)) {
            $this->logger->info('Intentando obtener archivos de $_FILES en middleware:', [
                'files' => array_keys($_FILES)
            ]);
        }
    }
    
    return $next($request, $response);
});

// e.g: $app->add(new \Slim\Csrf\Guard);
