# Documentación Técnica - Sistema de Reserva de Salas "GreenWork"

Esta documentación técnica proporciona una visión detallada de la arquitectura, componentes, tecnologías y estructura del sistema de reserva de salas "GreenWork". El objetivo es ofrecer a los nuevos desarrolladores una guía completa para entender cómo funciona el proyecto.

## Índice
1. [Visión General del Proyecto](#visión-general-del-proyecto)
2. [Arquitectura General](#arquitectura-general)
3. [Backend](#backend)
   - [Tecnologías Utilizadas](#tecnologías-utilizadas-backend)
   - [Estructura de Carpetas](#estructura-de-carpetas-backend)
   - [Modelos de Datos](#modelos-de-datos)
   - [Rutas API](#rutas-api)
   - [Autenticación y Autorización](#autenticación-y-autorización)
   - [Middleware](#middleware)
   - [Configuración de Base de Datos](#configuración-de-base-de-datos)
   - [Manejo de Imágenes](#manejo-de-imágenes)
   - [Testing](#testing)
4. [Frontend](#frontend)
   - [Tecnologías Utilizadas](#tecnologías-utilizadas-frontend)
   - [Estructura de Carpetas](#estructura-de-carpetas-frontend)
   - [Componentes Principales](#componentes-principales)
   - [Contextos](#contextos)
   - [Servicios](#servicios)
   - [Hooks Personalizados](#hooks-personalizados)
   - [Rutas y Navegación](#rutas-y-navegación)
   - [Estado Global](#estado-global)
   - [Estilización](#estilización)
5. [Integración Backend-Frontend](#integración-backend-frontend)
6. [Flujos de Trabajo Principales](#flujos-de-trabajo-principales)
7. [Herramientas de Desarrollo](#herramientas-de-desarrollo)
8. [Guía de Despliegue](#guía-de-despliegue)
9. [Consideraciones de Seguridad](#consideraciones-de-seguridad)
10. [Estrategia de Testing](#estrategia-de-testing)
11. [Mejores Prácticas](#mejores-prácticas)

---

## Visión General del Proyecto

"GreenWork" es un sistema de reserva de salas de coworking que permite a los usuarios buscar, reservar y gestionar espacios de trabajo. El sistema está diseñado con una arquitectura cliente-servidor, dividida en:

- **Backend**: API RESTful desarrollada con PHP y el framework Slim
- **Frontend**: Aplicación SPA (Single Page Application) desarrollada con React y TypeScript

El sistema soporta diferentes roles de usuario (usuario regular y administrador) con diferentes niveles de acceso y funcionalidades.

## Arquitectura General

La aplicación sigue una arquitectura de microservicios, donde el backend proporciona una API RESTful que es consumida por el frontend. La comunicación entre ambas partes se realiza mediante peticiones HTTP con formato JSON.

**Diagrama de Arquitectura:**

```
+------------------+        +------------------+        +------------------+
|                  |        |                  |        |                  |
|    Frontend      |<------>|     Backend      |<------>|    Database      |
|    (React)       |   API  |   (Slim PHP)     |  SQL   |    (MySQL)       |
|                  |        |                  |        |                  |
+------------------+        +------------------+        +------------------+
```

## Backend

### Tecnologías Utilizadas (Backend)

- **PHP**: Lenguaje de programación base
- **Slim Framework 3**: Framework PHP ligero para crear APIs RESTful
- **Eloquent ORM**: ORM de Laravel utilizado para la interacción con la base de datos
- **Firebase/JWT**: Biblioteca para la generación y validación de tokens JWT
- **Monolog**: Sistema de logging
- **PHP-CS-Fixer & PHP_CodeSniffer**: Herramientas de análisis de código
- **PHPUnit**: Framework de testing para PHP
- **MySQL**: Sistema de gestión de base de datos relacional

### Estructura de Carpetas (Backend)

```
backend/
├── captainhook.json              # Configuración de git hooks
├── composer.json                 # Dependencias del proyecto
├── composer.lock
├── CONTRIBUTING.md               # Guía de contribución
├── docker-compose.yml            # Configuración de Docker
├── phpcs.xml                     # Configuración de PHP_CodeSniffer
├── phpunit.xml                   # Configuración de PHPUnit
├── README.md                     # Documentación del backend
├── setup_database.sql            # Script de inicialización de base de datos
├── logs/                         # Logs de la aplicación
├── models/                       # Modelos Eloquent
│   ├── company.php               # Modelo de compañía
│   ├── image.php                 # Modelo para gestión de imágenes
│   ├── reservation.php           # Modelo de reserva
│   ├── room.php                  # Modelo de sala
│   ├── token.php                 # Modelo para gestión de tokens
│   └── user.php                  # Modelo de usuario
├── public/                       # Punto de entrada de la aplicación
│   └── index.php                 # Archivo principal
├── routes/                       # Definición de rutas API por recurso
│   ├── auth.php                  # Rutas de autenticación
│   ├── companies.php             # Rutas para gestión de compañías
│   ├── images.php                # Rutas para gestión de imágenes
│   ├── reservations.php          # Rutas para gestión de reservas
│   ├── rooms.php                 # Rutas para gestión de salas
│   └── users.php                 # Rutas para gestión de usuarios
├── src/                          # Código fuente principal
│   ├── AuthMiddleware.php        # Middleware de autenticación
│   ├── dependencies.php          # Configuración de dependencias
│   ├── FormDataHandler.php       # Manejador de datos de formularios
│   ├── middleware.php            # Configuración de middleware global
│   ├── RoleBasedAuthMiddleware.php # Middleware de autorización basada en roles
│   ├── routes.php                # Configuración de rutas generales
│   ├── settings.php              # Configuración de la aplicación
│   ├── Config/                   # Configuraciones adicionales
│   ├── Controllers/              # Controladores (lógica de negocio)
│   ├── Middleware/               # Middleware adicional
│   ├── Models/                   # Modelos adicionales
│   ├── Routes/                   # Definiciones de rutas adicionales
│   └── Services/                 # Servicios de la aplicación
├── templates/                    # Plantillas (si es necesario)
│   └── index.phtml
├── tests/                        # Tests de la aplicación
│   ├── bootstrap.php             # Configuración inicial para tests
│   ├── setup_test_database.sql   # Script para crear base de datos de pruebas
│   └── Functional/               # Tests funcionales
│       ├── AuthTest.php          # Tests de autenticación
│       ├── BaseTestCase.php      # Caso base para tests
│       ├── HomepageTest.php      # Tests de página principal
│       └── RoomTest.php          # Tests de salas
└── vendor/                       # Dependencias instaladas (composer)
```

### Modelos de Datos

La aplicación utiliza Eloquent ORM para definir los modelos de datos y sus relaciones:

#### User (users.php)
```php
class User extends Model {
    public $timestamps = false;
    protected $table = 'users';
    protected $fillable = ['id', 'first_name', 'last_name', 'email', 'password', 'role', 'preferred_language'];
    
    // Relación con las compañías: un usuario puede tener muchas compañías
    public function companies() {
        return $this->hasMany('Company', 'user_id');
    }
}
```

#### Company (company.php)
```php
class Company extends Model {
    public $timestamps = false;
    protected $table = 'companies';
    protected $fillable = ['id', 'user_id', 'name', 'email', 'phone', 'address'];
    
    // Relación con el usuario: una compañía pertenece a un usuario
    public function user() {
        return $this->belongsTo('User', 'user_id');
    }
    
    // Relación con las salas: una compañía tiene muchas salas
    public function rooms() {
        return $this->hasMany('Room', 'company_id');
    }
}
```

#### Room (room.php)
```php
class Room extends Model {
    public $timestamps = false;
    protected $table = 'rooms';
    protected $fillable = ['id', 'company_id', 'name', 'capacity', 'status', 'description'];
    
    // Relación con la compañía: una sala pertenece a una compañía
    public function company() {
        return $this->belongsTo('Company', 'company_id');
    }
    
    // Relación con las reservas: una sala tiene muchas reservas
    public function reservations() {
        return $this->hasMany('Reservation', 'room_id');
    }
}
```

#### Reservation (reservation.php)
```php
class Reservation extends Model {
    public $timestamps = false;
    protected $table = 'reservations';
    protected $fillable = ['id', 'user_id', 'room_id', 'date', 'start_time', 'end_time', 'status'];
    
    // Relación con el usuario: una reserva pertenece a un usuario
    public function user() {
        return $this->belongsTo('User', 'user_id');
    }
    
    // Relación con la sala: una reserva pertenece a una sala
    public function room() {
        return $this->belongsTo('Room', 'room_id');
    }
}
```

#### Token (token.php)
```php
class Token extends Model {
    public $timestamps = false;
    protected $table = 'tokens';
    protected $fillable = ['id', 'user_id', 'refresh_token', 'expires_at', 'is_revoked', 'created_at'];
    
    // Relación con user
    public function user() {
        return $this->belongsTo('User', 'user_id');
    }
    
    // Check if token is expired
    public function isExpired() {
        return strtotime($this->expires_at) < time();
    }
    
    // Check if token is valid
    public function isValid() {
        return !$this->is_revoked && !$this->isExpired();
    }
}
```

#### Image (image.php)
```php
class Image extends Model {
    public $timestamps = false;
    protected $table = 'images';
    protected $primaryKey = 'id_image';
    protected $fillable = ['imagescol', 'name'];
    public $incrementing = true;
}
```

### Rutas API

La API ofrece los siguientes endpoints:

#### Autenticación
- `POST /api/login` - Autenticar usuario y obtener tokens JWT (access token + refresh token)
- `POST /api/refresh` - Obtener un nuevo access token usando un refresh token
- `POST /api/logout` - Revocar refresh token (logout)
- `GET /api/me` - Obtener detalles del usuario autenticado
- `POST /api/forgot-password` - Solicitar restablecimiento de contraseña
- `POST /api/reset-password` - Restablecer contraseña usando token

#### Usuarios
- `GET /api/users` - Obtener todos los usuarios (autenticado)
- `GET /api/users/{id}` - Obtener usuario por ID (autenticado)
- `POST /api/users` - Crear nuevo usuario (solo admin)
- `POST /api/register` - Registrar nuevo usuario
- `PUT /api/users/{id}` - Actualizar usuario (cuenta propia o admin)
- `DELETE /api/users/{id}` - Eliminar usuario (solo admin)

#### Compañías
- `GET /api/companies` - Obtener todas las compañías (autenticado)
- `GET /api/companies/{id}` - Obtener compañía por ID (autenticado)
- `GET /api/users/{id}/companies` - Obtener compañías por ID de usuario (compañías propias o admin)
- `POST /api/companies` - Crear nueva compañía (autenticado)
- `PUT /api/companies/{id}` - Actualizar compañía (propietario de la compañía o admin)
- `DELETE /api/companies/{id}` - Eliminar compañía (propietario de la compañía o admin)

#### Salas
- `GET /api/rooms` - Obtener todas las salas (autenticado)
- `GET /api/rooms/{id}` - Obtener sala por ID (autenticado)
- `GET /api/companies/{id}/rooms` - Obtener salas por ID de compañía (autenticado)
- `POST /api/rooms` - Crear nueva sala (autenticado)
- `PUT /api/rooms/{id}` - Actualizar sala (propietario de la sala o admin)
- `DELETE /api/rooms/{id}` - Eliminar sala (propietario de la sala o admin)

#### Reservas
- `GET /api/reservations` - Obtener todas las reservas (solo admin)
- `GET /api/reservations/{id}` - Obtener reserva por ID (propietario de la reserva o admin)
- `GET /api/users/{id}/reservations` - Obtener reservas del usuario (reservas propias o admin)
- `GET /api/rooms/{id}/reservations` - Obtener reservas de la sala (autenticado)
- `POST /api/reservations` - Crear nueva reserva (autenticado)
- `PUT /api/reservations/{id}` - Actualizar reserva (propietario de la reserva o admin)
- `DELETE /api/reservations/{id}` - Eliminar reserva (propietario de la reserva o admin)

### Autenticación y Autorización

El sistema utiliza autenticación basada en JWT (JSON Web Tokens) con los siguientes componentes:

#### Tokens
- **Access Token**: Token de corta duración (1 hora) para acceder a recursos protegidos
- **Refresh Token**: Token de larga duración (30 días) para obtener nuevos access tokens

#### Middleware de Autenticación (AuthMiddleware.php)
Verifica la validez del token JWT y extrae información del usuario:

```php
class AuthMiddleware {
    private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, $next) {
        // Obtener JWT del header
        $token = $request->getHeaderLine('Authorization');

        if (!$token) {
            return $response->withJson([
                'error' => true,
                'message' => 'Authorization token required'
            ], 401);
        }

        // Eliminar "Bearer " del token
        $token = str_replace('Bearer ', '', $token);

        try {
            // Verificar token y obtener datos del usuario
            $userData = $this->verifyToken($token);

            // Añadir datos del usuario a la request para uso en las rutas
            $request = $request->withAttribute('user', $userData);

            // Llamar al siguiente middleware
            $response = $next($request, $response);
            return $response;
        } catch (\Exception $e) {
            return $response->withJson([
                'error' => true,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    private function verifyToken($token) {
        // Implementación de verificación de token
    }
}
```

#### Middleware de Autorización Basada en Roles (RoleBasedAuthMiddleware.php)
Verifica si el usuario tiene el rol requerido para acceder a un recurso:

```php
class RoleBasedAuthMiddleware {
    private $container;
    private $allowedRoles;

    public function __construct($container, array $allowedRoles) {
        $this->container = $container;
        $this->allowedRoles = $allowedRoles;
    }

    public function __invoke(Request $request, Response $response, $next) {
        $user = $request->getAttribute('user');

        if (!$user || !isset($user['role'])) {
            return $response->withJson([
                'error' => true,
                'message' => 'Unauthorized: User not authenticated'
            ], 401);
        }

        // Verificar si el rol del usuario está permitido
        if (!in_array($user['role'], $this->allowedRoles)) {
            return $response->withJson([
                'error' => true,
                'message' => 'Forbidden: Insufficient permissions'
            ], 403);
        }

        // El usuario tiene el rol requerido, continuar
        return $next($request, $response);
    }
}
```

### Configuración de Base de Datos

La configuración de la base de datos se define en `src/settings.php`:

```php
'db' => [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'greenwork',
    'username' => 'root',
    'password' => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]
```

Y se inicializa en `src/dependencies.php`:

```php
$container['db'] = function ($container) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($container['settings']['db']);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};
```

### Testing

El backend incluye tests funcionales para verificar el correcto funcionamiento de la API:

- `AuthTest.php`: Prueba todas las funcionalidades de autenticación y autorización
- `RoomTest.php`: Prueba las operaciones CRUD en salas
- `HomepageTest.php`: Prueba el funcionamiento de la página principal

Para ejecutar los tests:

```bash
composer test:setup   # Configura la base de datos de pruebas
composer test         # Ejecuta los tests
# O ambos en un solo comando:
composer test:with-setup
```

## Frontend

### Tecnologías Utilizadas (Frontend)

- **React**: Biblioteca JavaScript para construir interfaces de usuario
- **TypeScript**: Superset tipado de JavaScript
- **React Router**: Enrutamiento para aplicaciones React
- **Axios**: Cliente HTTP para realizar peticiones a la API
- **TailwindCSS**: Framework de CSS utilitario para estilizar la aplicación
- **React Hot Toast**: Biblioteca para notificaciones
- **date-fns**: Biblioteca para manipulación de fechas
- **Vite**: Herramienta de compilación rápida para desarrollo web moderno

### Estructura de Carpetas (Frontend)

```
frontend/
├── eslint.config.js              # Configuración de ESLint
├── index.html                    # Punto de entrada HTML
├── package.json                  # Dependencias del proyecto
├── README.md                     # Documentación del frontend
├── tailwind.config.js            # Configuración de TailwindCSS
├── tsconfig.json                 # Configuración de TypeScript
├── tsconfig.node.json            # Configuración de TypeScript para Node
├── vite.config.ts                # Configuración de Vite
├── public/                       # Archivos estáticos públicos
│   └── vite.svg                  # Logo de Vite
└── src/                          # Código fuente de la aplicación
    ├── App.css                   # Estilos globales
    ├── App.tsx                   # Componente raíz de la aplicación
    ├── main.tsx                  # Punto de entrada de la aplicación
    ├── theme.css                 # Estilos del tema
    ├── vite-env.d.ts             # Tipos para Vite
    ├── assets/                   # Recursos estáticos
    │   └── Manual de usuario.pdf # Manual de usuario
    ├── components/               # Componentes reutilizables
    │   ├── admin/                # Componentes para la sección de administración
    │   │   ├── AdminLayout/      # Layout para la sección de administración
    │   │   ├── AdminRoute/       # Componente para rutas protegidas de admin
    │   │   └── common/           # Componentes comunes para admin
    │   ├── common/               # Componentes comunes generales
    │   │   ├── Button/           # Componente de botón
    │   │   ├── Card/             # Componente de tarjeta
    │   │   ├── CompanyImage/     # Componente para imágenes de compañías
    │   │   ├── EmptyState/       # Componente para estados vacíos
    │   │   ├── FormInput/        # Componente de entrada de formulario
    │   │   ├── GradientText/     # Componente para texto con gradiente
    │   │   ├── LoadingSpinner/   # Componente de spinner de carga
    │   │   ├── ReservationCard/  # Componente para tarjetas de reserva
    │   │   ├── RoomImage/        # Componente para imágenes de salas
    │   │   ├── Section/          # Componente de sección
    │   │   ├── Tab/              # Componente de pestaña
    │   │   └── ThemeToggle/      # Componente para cambiar el tema
    │   ├── forms/                # Componentes relacionados con formularios
    │   │   └── Button/           # Componente de botón para formularios
    │   └── Header/               # Componente de cabecera
    │       └── index.tsx
    ├── contexts/                 # Contextos de React
    │   ├── AuthContext.tsx       # Contexto de autenticación
    │   └── ThemeContext.tsx      # Contexto de tema
    ├── hooks/                    # Hooks personalizados
    │   ├── useCTA.ts             # Hook para llamadas a la acción
    │   └── useFeatures.ts        # Hook para características dinámicas
    ├── screens/                  # Pantallas/páginas de la aplicación
    │   ├── Admin/                # Pantallas de administración
    │   │   ├── Companies/        # Gestión de compañías
    │   │   ├── Dashboard/        # Dashboard de administración
    │   │   ├── Reservations/     # Gestión de reservas
    │   │   ├── Rooms/            # Gestión de salas
    │   │   └── Users/            # Gestión de usuarios
    │   ├── Dashboard/            # Dashboard del usuario
    │   │   └── index.tsx
    │   ├── ForgotPassword/       # Recuperación de contraseña
    │   │   └── index.tsx
    │   ├── Home/                 # Página principal
    │   │   ├── index.tsx
    │   │   └── components/       # Componentes específicos de la página
    │   ├── Login/                # Inicio de sesión
    │   │   ├── index.tsx
    │   │   └── components/       # Componentes específicos de la página
    │   ├── Profile/              # Perfil de usuario
    │   │   ├── index.tsx
    │   │   └── components/       # Componentes específicos de la página
    │   ├── Reservations/         # Gestión de reservas del usuario
    │   ├── RoomAvailability/     # Disponibilidad de salas
    │   └── Rooms/                # Listado de salas
    └── services/                 # Servicios para la comunicación con la API
        ├── admin.ts              # Servicios de administración
        ├── adminUserService.ts   # Servicios de administración de usuarios
        ├── api.ts                # Configuración general de API
        ├── auth.ts               # Servicios de autenticación
        ├── companies.ts          # Servicios de compañías
        ├── image.ts              # Servicios de imágenes
        ├── reservations.ts       # Servicios de reservas
        └── rooms.ts              # Servicios de salas
```

### Componentes Principales

#### Header
Barra de navegación principal de la aplicación con enlaces a las diferentes secciones.

```tsx
// Componente Header que gestiona la navegación principal
const Header = (): JSX.Element => {
  const navigate = useNavigate()
  const location = useLocation()
  const { user, logout, profileImage } = useAuth()
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false)

  const handleLogout = () => {
    logout()
    navigate('/login')
  }
  
  // Función para determinar si una ruta está activa
  const isActiveRoute = (path: string): boolean => {
    // Verifica si la ruta actual comienza con el path del enlace
    return location.pathname === path || 
           (path !== '/' && location.pathname.startsWith(path))
  }
  
  // Resto del componente...
```

#### AdminLayout
Layout utilizado para todas las páginas de administración, incluye un sidebar con navegación.

```tsx
// Componente de layout para sección de administración
const AdminLayout = ({ children }: AdminLayoutProps): JSX.Element => {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const { user } = useAuth()
  const location = useLocation()

  // Comprobar si la ruta actual coincide con la ruta del enlace
  const isCurrentPath = (path: string) => {
    return location.pathname === path
  }

  // Opciones del menú
  const menuItems = [
    {
      label: 'Panel',
      path: '/admin',
      icon: (/* SVG icon */)
    },
    // Otros elementos del menú...
  ]

  // Resto del componente...
```

#### Card
Componente reutilizable para mostrar información en tarjetas con opciones flexibles.

```tsx
// Componente de tarjeta para mostrar información
const Card = ({
  children,
  className = '',
  onClick,
  title,
  subtitle,
  description,
  imageUrl,
  imageComponent,
  actionText,
  onAction
}: CardProps): JSX.Element => {
  const baseStyles = 'p-6 rounded-xl bg-white shadow-sm relative'
  const clickStyles = onClick ? 'cursor-pointer hover:shadow-md transition-shadow duration-300' : ''

  // Si tenemos datos estructurados, mostrar una tarjeta estructurada
  if (title || subtitle || description) {
    // Implementación de tarjeta estructurada...
  }

  // Por defecto, mostrar una tarjeta básica
  return (
    <div className={`${baseStyles} ${clickStyles} ${className}`} onClick={onClick}>
      {children}
    </div>
  )
}
```

#### AdminTable
Tabla genérica para mostrar datos en las secciones de administración.

```tsx
// Componente de tabla para secciones administrativas
function AdminTable<T>({
  data,
  columns,
  onEdit,
  onDelete,
  onView,
  keyExtractor,
  isLoading = false
}: AdminTableProps<T>): JSX.Element {
  if (isLoading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#1a472a]"></div>
      </div>
    )
  }

  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        {/* Implementación de la tabla... */}
      </table>
    </div>
  )
}
```

### Contextos

#### AuthContext
Gestiona el estado de autenticación, tokens y datos del usuario en toda la aplicación.

```tsx
// AuthContext para gestión de autenticación global
interface User {
  id: string
  first_name: string
  last_name: string
  email: string
  role: string
}

interface AuthContextType {
  user: User | null
  token: string | null
  isLoading: boolean
  error: string | null
  profileImage: string | undefined
  login: (email: string, password: string) => Promise<void>
  register: (firstName: string, lastName: string, email: string, password: string) => Promise<void>
  logout: () => void
  clearError: () => void
  updateProfile: (firstName: string, lastName: string) => Promise<void>
  updateProfileImage: (file: File) => Promise<void>
  changePassword: (currentPassword: string, newPassword: string) => Promise<void>
  deleteAccount: (password: string) => Promise<void>
}

export const AuthContext = createContext<AuthContextType>({
  // Valores por defecto...
})

export const AuthProvider = ({ children }: AuthProviderProps) => {
  const [user, setUser] = useState<User | null>(null)
  const [token, setToken] = useState<string | null>(localStorage.getItem('token'))
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [profileImage, setProfileImage] = useState<string | undefined>(
    localStorage.getItem('profileImage') || undefined
  )

  // Implementación de los métodos del contexto...

  return (
    <AuthContext.Provider value={/* valores del contexto */}>
      {children}
    </AuthContext.Provider>
  )
}

export const useAuth = () => React.useContext(AuthContext)
```

#### ThemeContext
Gestiona el tema visual de la aplicación.

```tsx
// ThemeContext para gestión del tema visual
const defaultTheme = {
  colors: {
    primary: '#1a472a', // Verde oscuro como color principal
    secondary: '#333333', // Gris oscuro como color secundario
    accent: '#5c9b7d', // Color adicional - verde medio
    // Otros colores...
  },
  fonts: {
    main: "'Inter', 'system-ui', sans-serif",
    heading: "'Inter', 'system-ui', sans-serif"
  },
  // Otras propiedades del tema...
}

interface ThemeContextType {
  theme: Theme
  setTheme: (theme: Theme) => void
}

export const ThemeContext = createContext<ThemeContextType>({
  theme: defaultTheme,
  setTheme: () => {}
})

export const ThemeProvider = ({ children }: ThemeProviderProps) => {
  const [theme, setTheme] = useState<Theme>(defaultTheme)
  
  // Implementación...

  return (
    <ThemeContext.Provider value={{ theme, setTheme }}>
      {children}
    </ThemeContext.Provider>
  )
}

export const useTheme = () => useContext(ThemeContext)
```

### Servicios

Los servicios manejan la comunicación con la API del backend:

#### api.ts
Configura Axios para las peticiones HTTP y maneja los tokens de autenticación.

```tsx
// Configuración base de Axios para peticiones API
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080'

export const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Interceptor para añadir token a las peticiones
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token && config.headers) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor para manejar errores de autenticación
api.interceptors.response.use(
  (response) => {
    return response
  },
  async (error) => {
    // Manejo de errores, incluyendo refresh token...
  }
)
```

#### auth.ts
Maneja operaciones de autenticación como login, registro, y actualización de perfil.

```tsx
// Servicios relacionados con autenticación
export const login = async (credentials: LoginCredentials): Promise<AuthResponse> => {
  try {
    const { data } = await api.post<AuthResponse>('/api/login', credentials)
    return data
  } catch (error) {
    // Manejo de errores...
  }
}

export const register = async (data: RegisterData): Promise<AuthResponse> => {
  try {
    const { data: responseData } = await api.post<AuthResponse>('/api/register', data)
    return responseData
  } catch (error) {
    // Manejo de errores...
  }
}

// Otros métodos relacionados con la autenticación...
```

#### rooms.ts, reservations.ts, companies.ts
Servicios para gestionar las operaciones CRUD de cada entidad.

### Rutas y Navegación

La aplicación utiliza React Router para la navegación entre pantallas:

```tsx
// Configuración de rutas en App.tsx
function AppRoutes(): JSX.Element {
  const { user } = useAuth()

  return (
    <Routes>
      {/* Redirigir la raíz al dashboard si está autenticado, o al login si no */}
      <Route
        path="/"
        element={user ? <Navigate to="/dashboard" replace /> : <Navigate to="/login" replace />}
      />
      
      {/* Rutas públicas */}
      <Route path="/login" element={<Login />} />
      <Route path="/forgot-password" element={<ForgotPassword />} />
      
      {/* Rutas protegidas para usuarios */}
      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <Dashboard />
          </ProtectedRoute>
        }
      />
      <Route
        path="/rooms"
        element={
          <ProtectedRoute>
            <Rooms />
          </ProtectedRoute>
        }
      />
      {/* Otras rutas protegidas... */}
      
      {/* Rutas protegidas para administradores */}
      <Route
        path="/admin"
        element={
          <AdminRoute>
            <AdminDashboard />
          </AdminRoute>
        }
      />
      <Route
        path="/admin/users"
        element={
          <AdminRoute>
            <AdminUsers />
          </AdminRoute>
        }
      />
      {/* Otras rutas protegidas de administración... */}
    </Routes>
  )
}
```

### Estilización

La aplicación utiliza TailwindCSS para estilizar los componentes:

```tsx
// Ejemplo de estilos con TailwindCSS
<div className="container mx-auto px-4 py-6">
  <h1 className="text-3xl font-bold mb-8 text-gray-800">Dashboard</h1>

  {error && <div className="mb-6 p-4 bg-red-50 text-red-500 rounded-lg">{error}</div>}

  <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <Section title="Próximas Reservas">
      {/* Contenido de la sección... */}
    </Section>
  </div>
</div>
```

También se utiliza un sistema de temas para mantener la coherencia visual:

```tsx
// Ejemplo de uso del sistema de temas
<button
  onClick={() => navigate('/rooms')}
  className="w-full py-2 rounded-md text-sm font-medium transition-colors cursor-pointer"
  style={{ backgroundColor: theme.colors.primary, color: 'white' }}
>
  Reservar ahora
</button>
```

## Integración Backend-Frontend

La comunicación entre el backend y el frontend se realiza mediante la API RESTful:

1. **Autenticación**: 
   - El frontend envía credenciales (email y contraseña) al backend
   - El backend valida las credenciales y devuelve tokens JWT
   - El frontend almacena los tokens en localStorage y los usa para peticiones subsiguientes

2. **Operaciones CRUD**:
   - Cada entidad (usuarios, compañías, salas, reservas) tiene su propio servicio en el frontend
   - Los servicios utilizan Axios para comunicarse con la API del backend
   - Las operaciones se realizan mediante los métodos HTTP estándar (GET, POST, PUT, DELETE)

3. **Gestión de errores**:
   - El backend devuelve códigos de estado HTTP apropiados
   - El frontend maneja estos códigos y muestra mensajes de error adecuados
   - Se utiliza un sistema de interceptores en Axios para manejar errores de autenticación y refrescar tokens

## Flujos de Trabajo Principales

### Flujo de Autenticación

1. **Login**:
   - Usuario ingresa credenciales en la pantalla de login
   - Frontend envía petición a `/api/login`
   - Backend valida credenciales, genera tokens y los devuelve
   - Frontend almacena tokens y redirige al dashboard

2. **Registro**:
   - Usuario completa formulario de registro
   - Frontend envía datos a `/api/register`
   - Backend crea el usuario, genera tokens y los devuelve
   - Frontend almacena tokens y redirige al dashboard

3. **Refresh Token**:
   - Cuando un token expira, el interceptor de Axios detecta el error 401
   - Frontend envía el refresh token a `/api/refresh`
   - Backend valida el refresh token y genera un nuevo access token
   - Frontend actualiza el token y reintenta la petición original

### Flujo de Reserva de Salas

1. **Listar Salas**:
   - Usuario accede a la sección de salas
   - Frontend obtiene lista de salas de `/api/rooms`
   - Se muestran las salas disponibles con filtros

2. **Ver Disponibilidad**:
   - Usuario selecciona una sala para ver disponibilidad
   - Frontend obtiene detalles de la sala y reservas existentes
   - Se muestra un calendario con las fechas disponibles

3. **Realizar Reserva**:
   - Usuario selecciona fecha y hora para reservar
   - Frontend envía datos a `/api/reservations`
   - Backend crea la reserva y actualiza el estado de la sala
   - Se muestra confirmación al usuario

## Herramientas de Desarrollo

### Backend
- **Composer**: Gestor de dependencias para PHP
- **PHP-CS-Fixer**: Herramienta para formatear código PHP
- **PHP_CodeSniffer**: Herramienta para analizar la calidad del código
- **PHPUnit**: Framework de testing para PHP
- **CaptainHook**: Herramienta para gestionar Git hooks

### Frontend
- **npm/yarn**: Gestores de paquetes para JavaScript
- **Vite**: Herramienta de construcción y servidor de desarrollo
- **ESLint**: Analizador de código para JavaScript/TypeScript
- **TypeScript**: Superset tipado de JavaScript

## Guía de Despliegue

### Backend

1. **Requisitos del Servidor**:
   - PHP 7.4 o superior
   - MySQL 5.7 o superior
   - Extensiones PHP: PDO, JSON, mbstring

2. **Pasos de Despliegue**:
   ```bash
   # Clonar repositorio
   git clone [repo-url]
   cd backend
   
   # Instalar dependencias
   composer install --no-dev
   
   # Configurar base de datos
   # Editar src/settings.php con los datos de conexión
   
   # Crear estructura de base de datos
   mysql -u [usuario] -p[contraseña] < setup_database.sql
   
   # Configuración de servidor web (Apache/Nginx)
   # Apuntar al directorio /public
   ```

### Frontend

1. **Requisitos**:
   - Node.js 14 o superior
   - npm o yarn

2. **Pasos de Despliegue**:
   ```bash
   # Clonar repositorio
   git clone [repo-url]
   cd frontend
   
   # Instalar dependencias
   npm install
   
   # Configurar variables de entorno
   # Crear archivo .env con VITE_API_URL=http://api.example.com
   
   # Construir para producción
   npm run build
   
   # Desplegar contenido de la carpeta /dist en un servidor web estático
   ```

## Consideraciones de Seguridad

1. **Autenticación**:
   - Tokens JWT con expiración corta (1 hora)
   - Sistema de refresh token para mejorar seguridad
   - Almacenamiento seguro de contraseñas con hash (password_hash)

2. **Autorización**:
   - Control de acceso basado en roles (RBAC)
   - Middleware de autorización para proteger rutas sensibles
   - Verificación de propiedad en operaciones de actualización/eliminación

3. **Protección contra Ataques**:
   - Validación de datos en backend y frontend
   - Protección contra CSRF usando tokens
   - Encabezados de seguridad apropiados

## Estrategia de Testing

### Backend
- **Tests Funcionales**: Pruebas de integración para verificar el funcionamiento de la API
- **Tests de Autenticación**: Verificación de todos los flujos de autenticación
- **Tests de Autorización**: Comprobación de permisos y roles

### Frontend
- **Tests de Componentes**: Verificación del comportamiento de componentes UI
- **Tests de Integración**: Pruebas de flujos completos de usuario
- **Tests End-to-End**: Verificación de la interacción entre frontend y backend

## Mejores Prácticas

1. **Código**:
   - Seguir estándares de codificación (PSR para PHP, ESLint para JS/TS)
   - Utilizar tipado estático cuando sea posible (TypeScript)
   - Documentar funciones y clases importantes

2. **Arquitectura**:
   - Separación clara de responsabilidades (MVC en backend, componentes/servicios en frontend)
   - DRY (Don't Repeat Yourself): Reutilizar código mediante componentes y servicios
   - SOLID: Seguir principios de diseño orientado a objetos

3. **Operaciones**:
   - Implementar CI/CD para automatizar pruebas y despliegue
   - Monitorizar errores en producción
   - Mantener el registro (logging) adecuado para diagnosticar problemas

---

Este documento proporciona una visión completa del sistema de reserva de salas "GreenWork". Para preguntas adicionales o problemas específicos, consulte el repositorio de código fuente o contacte al equipo de desarrollo.
