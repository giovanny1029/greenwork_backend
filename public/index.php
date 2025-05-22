<?php
// Desactivar mensajes de deprecated para evitar advertencias con Slim 3.12 en PHP 8.4
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Registrar modelos
require __DIR__ . '/../models/user.php';
require __DIR__ . '/../models/company.php';
require __DIR__ . '/../models/room.php';
require __DIR__ . '/../models/reservation.php';
require __DIR__ . '/../models/token.php';
require __DIR__ . '/../models/image.php';

// Registrar rutas por modelo
require __DIR__ . '/../routes/auth.php';
require __DIR__ . '/../routes/users.php';
require __DIR__ . '/../routes/companies.php';
require __DIR__ . '/../routes/rooms.php';
require __DIR__ . '/../routes/reservations.php';
require __DIR__ . '/../routes/images.php';

// Inicializar Eloquent 
$app->getContainer()->get("db");

// Run app
$app->run();
