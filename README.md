# Room Reservation System Backend

This is the backend API for a room reservation system, built with Slim Framework 3 and Eloquent ORM. The system provides CRUD operations for users, companies, rooms, and reservations, with comprehensive authentication and authorization features.

## Features

- User registration and authentication with JWT
- Role-based access control
- Refresh token system for secure authentication
- Password reset functionality
- CRUD operations for users, companies, rooms, and reservations
- Relationship between users and companies
- Comprehensive API endpoints
- CORS support for frontend interaction
- Code quality tools integration

## Install the Application

Run this command from the directory in which you want to install your new Slim Framework application.
```
composer create-project slim/slim-skeleton slimphp-CRUD
```

## Install Eloquent ORM

This application also use eloquent ORM from Laravel. The documentation is [here](https://www.slimframework.com/docs/cookbook/database-eloquent.html).
```
composer require illuminate/database "~5.1"
```

## Database

The application connect to MySQL database. The configuration of database added in setting and  dependencies file.

Database connection settings on 'src/settings.php'
```
'db' => [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'example',
    'username' => 'root',
    'password' => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]
```

Service factory for the ORM on 'src/dependencies.php'
```
$container['db'] = function ($container) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($container['settings']['db']);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};
```

Also don't forget to initialize Eloquent on 'public/index.php'
```
$app->getContainer()->get("db");
```

The database schema could be imported from example.sql. This app use database named 'example' and 'books' table which has 4 columns. 
```
CREATE TABLE IF NOT EXISTS `books` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `author` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
```

## Model
Create 'models' folder on the root path and put schema file there. After that initialiaze model file path on index.php file.

Register model on 'public/index.php'
```
require '/../Models/Book.php';
```

## Route
Create 'routes' folder on the root path and put route file there. After that initialiaze route file path on index.php file.

Register routes by model on 'public/index.php'
```
require __DIR__ . '/../routes/books.php';
```

## API Endpoints

### Authentication
- `POST /api/login` - Authenticate user and get JWT tokens (access token + refresh token)
- `POST /api/refresh` - Get a new access token using a refresh token
- `POST /api/logout` - Revoke refresh token (logout)
- `GET /api/me` - Get authenticated user details
- `POST /api/forgot-password` - Request a password reset
- `POST /api/reset-password` - Reset password using token

### Users
- `GET /api/users` - Get all users (authenticated)
- `GET /api/users/{id}` - Get user by ID (authenticated)
- `POST /api/users` - Create new user (admin only)
- `POST /api/register` - Register new user
- `PUT /api/users/{id}` - Update user (own account or admin)
- `DELETE /api/users/{id}` - Delete user (admin only)

### Companies
- `GET /api/companies` - Get all companies (authenticated)
- `GET /api/companies/{id}` - Get company by ID (authenticated)
- `GET /api/users/{id}/companies` - Get companies by user ID (own companies or admin)
- `POST /api/companies` - Create new company (authenticated)
- `PUT /api/companies/{id}` - Update company (company owner or admin)
- `DELETE /api/companies/{id}` - Delete company (company owner or admin)

### Rooms
- `GET /api/rooms` - Get all rooms (authenticated)
- `GET /api/rooms/{id}` - Get room by ID (authenticated)
- `GET /api/companies/{id}/rooms` - Get rooms by company ID (authenticated)
- `POST /api/rooms` - Create new room (authenticated)
- `PUT /api/rooms/{id}` - Update room (room owner or admin)
- `DELETE /api/rooms/{id}` - Delete room (room owner or admin)

### Reservations
- `GET /api/reservations` - Get all reservations (admin only)
- `GET /api/reservations/{id}` - Get reservation by ID (reservation owner or admin)
- `GET /api/users/{id}/reservations` - Get user's reservations (own reservations or admin)
- `GET /api/rooms/{id}/reservations` - Get room's reservations (authenticated)
- `POST /api/reservations` - Create new reservation (authenticated)
- `PUT /api/reservations/{id}` - Update reservation (reservation owner or admin)
- `DELETE /api/reservations/{id}` - Delete reservation (reservation owner or admin)

## Authentication

The system uses JWT (JSON Web Tokens) for authentication. Include the token in the Authorization header for all authenticated requests:

```
Authorization: Bearer [your-token]
```

The authentication system implements the following security features:

1. **Short-lived access tokens** (1 hour expiration)
2. **Refresh tokens** for obtaining new access tokens without re-authentication
3. **Token revocation** for logout functionality
4. **Password reset** via email tokens

## Testing

The API includes comprehensive tests for all authentication features.

1. Set up the test database:
```bash
composer test:setup
```

2. Run tests:
```bash
composer test
```

3. Or run both steps at once:
```bash
composer test:with-setup
```

Tests cover:
- User login and authentication
- Token refresh functionality
- Role-based authorization
- Password reset flow

## Code Quality Tools

This project uses several code quality tools:

- PHP CS Fixer for code formatting
- PHP_CodeSniffer for linting
- CaptainHook for Git hooks

Run the following commands:

```bash
# Check coding style
composer check-style

# Fix coding style
composer fix-style

# Run linting
composer lint

# Fix linting issues
composer lint:fix
```