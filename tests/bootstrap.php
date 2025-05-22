<?php
// filepath: c:\dev\giova\backend\tests\bootstrap.php

// Set up autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables for testing
putenv('JWT_SECRET=test-secret-key');

// Set up test database - here you would typically create a test database
// or use an in-memory SQLite database for testing

// Initialize application
$settings = require __DIR__ . '/../src/settings.php';
// Override settings for testing environment
$settings['settings']['db']['database'] = 'greenwork_test';

// Create app instance
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Register models
require __DIR__ . '/../models/user.php';
require __DIR__ . '/../models/company.php';
require __DIR__ . '/../models/room.php';
require __DIR__ . '/../models/reservation.php';

// Register route files
require __DIR__ . '/../routes/auth.php';
require __DIR__ . '/../routes/users.php';
require __DIR__ . '/../routes/companies.php';
require __DIR__ . '/../routes/rooms.php';
require __DIR__ . '/../routes/reservations.php';

// Initialize Eloquent
$container = $app->getContainer();
$container->get('db');

// Set up test database schema - you could run your setup_database.sql script here
// or define the schema directly for testing purposes.
