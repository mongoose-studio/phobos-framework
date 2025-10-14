# Phobos Framework
### by Mongoose Studio

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.txt)
[![Version](https://img.shields.io/badge/version-3.0.2-orange)](https://github.com/mongoose-studio/phobos-framework)


**PhobosFramework** es un framework PHP moderno, minimalista y de alto rendimiento, diseñado para construir APIs RESTful robustas y escalables. Toma inspiración de la ligereza de Slim y de la modularidad de Angular, pero su núcleo permanece libre de dependencias externas. Su sistema de routing es claro y flexible; admite parámetros tipo `:param`, wildcards avanzados (`*`, `**`) y módulos autocontenidos que ayudan a mantener la estructura en aplicaciones empresariales.

¿Que tiene de bueno esta versión? pues, encontrarás un `DI Container` con `autowiring` automático, `middleware` encadenados y compatibilidad con `PHP 8.3+` para asegurar tipos de forma completa. Suena muy técnico, pero entiéndelo de esta forma: se traduce en código más seguro, menos acoplamiento y pruebas más simples. Además, Phobos trae un `Observer` para debugging "en vivo", soporte `multi-tenant` nativo, versionado de APIs y orientado a API-first con conversión JSON automática. Desde microservicios hasta SSO servers, API gateways o backends para SPAs, Phobos da las herramientas necesarias sin complicar la experiencia del desarrollador.

Si trabajaste con `Phobos 1 o 2` —o incluso con `XWork (3 a 7)`— vas a reconocer el ADN: sigue siendo modular, con DAOs y rutas como pilares. Pero Phobos 3 se da un lavado de cara: mantiene lo bueno y suma cosas que hacen la vida del dev mucho más fácil. Piensa en helpers por todos lados, menos singletons por defecto, middleware e injections ordenadas, pipelines claros, un ciclo de vida más robusto y un observador que hace el debug menos doloroso. Añadimos servicios, configuración vía `.env`, `request`/`response` objects para entender exactamente qué pasa, librerías sólidas y un montón de pequeñas mejoras muy prácticas. En resumen: lo mismo de siempre, pero más limpio, más rápido, más amable y mas pulento XD 🇨🇱.

## Características Principales

- 🚀 **Ligero y Rápido** - Sin dependencias externas, puro PHP
- 💉 **Inyección de Dependencias** - Container con autowiring automático
- 🔄 **Sistema de Middleware** - Pipeline tipo "onion" para procesar requests
- 🎯 **Enrutamiento Avanzado** - Parámetros dinámicos, wildcards y grupos
- 📦 **Arquitectura Modular** - Organiza tu código en módulos independientes
- 🔍 **Sistema Observer** - Debugging en vivo del ciclo de vida
- ⚡ **PHP 8.3+** - Aprovecha las características más modernas de PHP

## Instalación

```bash
composer require mongoose-studio/phobos-framework
```

## Inicio Rápido

### 1. Estructura Básica

```
/app
  /Controllers
  /Middleware
  /Modules
  /Providers
/config
  app.php
  database.php
/public
  index.php
/storage
/.env
```

### 2. Punto de Entrada (`public/index.php`)

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

$app = Phobos::init(__DIR__)
    ->loadEnvironment()
    ->loadConfig()
    ->bootstrap(App\AppModule::class);

$response = $app->run();
$response->send();
```

### 3. Crear un Módulo

```php
<?php

namespace App;

use Phobos\Module\ModuleInterface;
use Phobos\Routing\Router;

class AppModule implements ModuleInterface
{
    public function routes(Router $router): void
    {
        $router->get('/', [HomeController::class, 'index']);
        
        $router->group(['prefix' => 'api'], function($r) {
            $r->get('/users', [UserController::class, 'index']);
            $r->get('/users/:id', [UserController::class, 'show']);
            $r->post('/users', [UserController::class, 'store']);
        });
    }

    public function middlewares(): array
    {
        return []; // Middlewares globales
    }

    public function providers(): array
    {
        return [DatabaseProvider::class, AuthProvider::class];
    }
}
```

### 4. Crear un Controller

```php
<?php

namespace App\Controllers;

use Phobos\Http\Request;
use Phobos\Http\Response;

class UserController
{
    public function __construct(
        private UserRepository $users,
        private Logger $logger
    ) {}

    public function index(Request $request): Response
    {
        $users = $this->users->all();
        return Response::json($users);
    }

    public function show(Request $request, string $id): Response
    {
        $user = $this->users->find($id);
        
        if (!$user) {
            return Response::error('Usuario no encontrado', 404);
        }
        
        return Response::json($user);
    }

    public function store(Request $request): Response
    {
        $data = $request->json();
        $user = $this->users->create($data);
        
        return Response::json($user, 201);
    }
}
```

## Documentación

### Enrutamiento

Phobos soporta múltiples métodos HTTP y patrones de rutas:

```php
// Métodos HTTP básicos
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/:id', [UserController::class, 'update']);
$router->delete('/users/:id', [UserController::class, 'destroy']);

// Parámetros dinámicos (sintaxis con dos puntos)
$router->get('/posts/:id', [PostController::class, 'show']);
$router->get('/users/:userId/posts/:postId', [PostController::class, 'userPost']);

// Wildcards
$router->get('/files/*/download', [FileController::class, 'download']); // Un segmento
$router->get('/docs/**', [DocController::class, 'serve']); // Múltiples segmentos

// Grupos con prefijo y middleware
$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function($r) {
    $r->get('/dashboard', [AdminController::class, 'dashboard']);
    $r->get('/users', [AdminController::class, 'users']);
});

// Rutas nombradas
$router->get('/profile/:id', [ProfileController::class, 'show'])->name('profile.show');

// Usar ruta nombrada
$url = route('profile.show', ['id' => 5]); // /profile/5
```

### Container de Dependencias

El Container resuelve automáticamente las dependencias:

```php
// Binding transient (nueva instancia cada vez)
container()->bind(UserRepository::class, DatabaseUserRepository::class);

// Singleton (instancia compartida)
container()->singleton(Logger::class, FileLogger::class);

// Instancia existente
container()->instance(Config::class, $config);

// Resolución manual
$logger = container()->make(Logger::class);

// Helpers globales
$logger = inject(Logger::class);
singleton(Cache::class, RedisCache::class);
```

### Middleware

Crea middleware implementando `MiddlewareInterface`:

```php
<?php

namespace App\Middleware;

use Phobos\Middleware\MiddlewareInterface;
use Phobos\Http\Request;
use Closure;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');
        
        if (!$this->isValidToken($token)) {
            return Response::error('No autorizado', 401);
        }
        
        return $next($request);
    }
    
    private function isValidToken(?string $token): bool
    {
        // Lógica de validación
        return $token !== null;
    }
}
```

Aplica middleware a rutas:

```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware(AuthMiddleware::class);

// O en grupos
$router->group(['middleware' => AuthMiddleware::class], function($r) {
    $r->get('/dashboard', [DashboardController::class, 'index']);
});
```

### Request y Response

**Request:**

```php
$method = $request->method(); // GET, POST, etc.
$path = $request->path(); // /users/123
$id = $request->param('id'); // Parámetro de ruta
$name = $request->query('name'); // Query string
$email = $request->input('email'); // POST/PUT data
$data = $request->json(); // JSON body completo
$token = $request->header('Authorization');

// Helpers
if ($request->isJson()) { }
if ($request->isPost()) { }
if ($request->isAjax()) { }
```

**Response:**

```php
// JSON
return Response::json(['message' => 'Success']);
return Response::json($data, 201); // Con código de estado

// HTML
return Response::html('<h1>Hola</h1>');

// Texto plano
return Response::text('Plain text');

// Error
return Response::error('Mensaje de error', 400);

// Vacío
return Response::empty(204);

// Con headers
return Response::json($data)
    ->header('X-Custom', 'value')
    ->status(200);

// Los controllers pueden retornar directamente
public function index(): array {
    return ['users' => $users]; // Automáticamente convertido a JSON
}
```

### Configuración

**Variables de Entorno (.env):**

```env
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
DB_NAME=myapp
DB_USER=root
DB_PASS=secret
```

Acceso:

```php
$env = env('APP_ENV', 'production');
$debug = env('APP_DEBUG', false);
```

**Archivos de Configuración (config/):**

```php
// config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'host' => env('DB_HOST', 'localhost'),
            'database' => env('DB_NAME'),
            'username' => env('DB_USER'),
            'password' => env('DB_PASS'),
        ]
    ]
];
```

Acceso con notación de puntos:

```php
$host = config('database.connections.mysql.host');
$default = config('database.default');

// Establecer en tiempo de ejecución
Config::set('app.timezone', 'America/Santiago');
```

### Service Providers

Los providers organizan el registro de servicios:

```php
<?php

namespace App\Providers;

use Phobos\Core\ServiceProvider;
use Phobos\Core\Container;

class DatabaseProvider extends ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(Database::class, function($c) {
            return new Database(
                config('database.connections.mysql')
            );
        });
    }

    public function boot(Container $container): void
    {
        // Ejecutado después de que todos los providers se registren
        $db = $container->make(Database::class);
        $db->connect();
    }
}
```

### Sistema Observer

El Observer permite debugging en vivo:

```php
// Registrar eventos
Observer::record('user.created', ['user_id' => 123]);
trace('cache.hit', ['key' => 'users']);

// Ver todos los eventos
Observer::dumpFormatted();

// Filtrar eventos
$routerEvents = Observer::filter('router.*');

// Resumen
$summary = Observer::summary();

// Habilitar/Deshabilitar
Observer::disable();
Observer::enable();
```

### Excepciones HTTP

```php
use Phobos\Exceptions\NotFoundException;
use Phobos\Exceptions\UnauthorizedException;
use Phobos\Exceptions\ValidationException;

// Lanzar excepciones
throw new NotFoundException('Usuario no encontrado');
throw new UnauthorizedException('Token inválido');
throw new ValidationException('Datos inválidos', [
    'email' => 'El email es requerido'
]);

// Helper rápido
abort(404, 'Recurso no encontrado');
abort(401);
```

### Helpers Globales

```php
// Acceso al framework
$app = phobos();
$container = container();
$req = request();

// DI
$logger = inject(Logger::class);

// Config/Env
$debug = env('APP_DEBUG');
$timezone = config('app.timezone');

// Paths
$base = base_path('app/Controllers');
$config = config_path('database.php');
$public = public_path('assets/logo.png');
$url = url('/api/users');

// Debugging
dd($variable); // Dump and die
dump($data); // Dump
dpre($array); // Dump con <pre>

// Utilidades
$result = tap($user, fn($u) => $u->save());
$value = value($callback);
if (blank($value)) { }
if (filled($value)) { }

// Ambiente
if (is_dev()) { }
if (is_prod()) { }
```

## Arquitectura

### Ciclo de Vida de la Aplicación

1. **Inicialización** - `Phobos::init()` crea la instancia singleton
2. **Ambiente** - `loadEnvironment()` carga variables de entorno
3. **Configuración** - `loadConfig()` prepara el sistema de configuración
4. **Bootstrap** - `bootstrap()` registra providers y rutas del módulo
5. **Ejecución** - `run()` captura request, ejecuta pipeline y retorna response

### Principios de Diseño

- **Module-First**: Organiza por funcionalidad, no por capas técnicas
- **Dependency Injection**: Todo se resuelve a través del Container
- **Middleware Pipeline**: Procesamiento tipo "onion" de requests
- **Convention over Configuration**: Convenciones sensatas con flexibilidad
- **Zero Dependencies**: El core no depende de librerías externas

## Testing

Phobos Framework incluye un suite completo de pruebas con más de 115 tests que cubren todos los componentes principales.

### Ejecutar Pruebas

```bash
# Instalar dependencias de desarrollo
composer install --dev

# Ejecutar todas las pruebas
composer test

# Ejecutar pruebas con cobertura
composer test:coverage

# Ejecutar un test específico
composer test:filter testName
```

### Estructura de Tests

```
tests/
├── Unit/               # Pruebas unitarias de componentes individuales
│   ├── Core/          # Container, Observer, ServiceProvider
│   ├── Routing/       # Router, Route, RouteMatch
│   ├── Http/          # Request, Response
│   ├── Middleware/    # Pipeline
│   └── Config/        # Config, EnvLoader
└── Integration/       # Pruebas de integración full-stack
    └── FullStackTest.php
```

### Cobertura de Tests

El suite de pruebas incluye:

- **ContainerTest** (23 tests) - DI container, autowiring, singletons, dependencias circulares
- **RouterTest** (23 tests) - Routing, parámetros, wildcards, grupos, middleware
- **RequestTest** (22 tests) - Headers, query params, JSON, route params
- **ResponseTest** (17 tests) - JSON/HTML/text responses, headers, códigos de estado
- **PipelineTest** (12 tests) - Middleware pipeline, orden de ejecución
- **ConfigTest** (13 tests) - Configuración, dot notation, lazy loading
- **FullStackTest** (7 tests) - Ciclo completo request-response

### Escribir Tests

Los tests siguen el patrón AAA (Arrange, Act, Assert) y usan PHPUnit 11.0:

```php
<?php

namespace PhobosFramework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Core\Container;

class MyTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function test_can_bind_service(): void
    {
        // Arrange
        $this->container->bind(MyService::class);

        // Act
        $service = $this->container->make(MyService::class);

        // Assert
        $this->assertInstanceOf(MyService::class, $service);
    }
}
```

### Testing en Desarrollo

Para probar cambios en el framework durante desarrollo local:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../PhobosFramework"
    }
  ],
  "require": {
    "mongoose-studio/phobos-framework": "^3.0"
  }
}
```

### Meta de Cobertura

El objetivo es mantener una cobertura de código ≥ 80% en todos los componentes principales. Ejecuta `composer test:coverage` para generar un reporte HTML en el directorio `coverage/`.

## Notas Importantes

- **PHP 8.3+ requerido** - Aprovecha promoted properties, named arguments, match expressions
- **Sin dependencias externas** - El core es completamente independiente
- **No hay manejador global de errores** - Implementa manejo de excepciones vía middleware
- **Singleton pattern** - Solo una instancia de `Phobos` por proceso
- **Parámetros de ruta** - Usa sintaxis `:param` (con dos puntos), no llaves `{param}`
- **Auto-conversión JSON** - Arrays retornados se convierten automáticamente a JSON

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE.txt) para más detalles.

## Autor

**Marcel Rojas**  
[marcelrojas16@gmail.com](mailto:marcelrojas16@gmail.com)  
__Mongoose Studio__

## Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/amazing-feature`)
3. Commit tus cambios (`git commit -m 'Add amazing feature'`)
4. Push a la rama (`git push origin feature/amazing-feature`)
5. Abre un Pull Request

---

**Phobos Framework** by Mongoose Studio
