# Phobos Framework Test Suite

Esta es la suite de pruebas para el Phobos Framework v3.0.2. (aún faltan algunas que se desarrollarán a futuro 😬)

## Estructura de Tests

```
tests/
├── Unit/              # Tests unitarios para componentes individuales
│   ├── Core/          # Container, Observer, Phobos
│   ├── Routing/       # Router, Route, RouteRegister
│   ├── Http/          # Request, Response
│   ├── Config/        # Config, EnvLoader
│   ├── Middleware/    # Pipeline
│   └── Exceptions/    # HTTP Exceptions
│
├── Integration/       # Tests de integración full-stack
│   └── FullStackTest.php
│
└── Fixtures/          # Clases de apoyo para tests
```

## Requisitos

- PHP >= 8.3
- PHPUnit ^11.0
- Composer

## Instalación

```bash
composer install
```

## Ejecutar Tests

### Todos los tests
```bash
composer test
```

O directamente con PHPUnit:
```bash
vendor/bin/phpunit
```

### Solo tests unitarios
```bash
vendor/bin/phpunit --testsuite Unit
```

### Solo tests de integración
```bash
vendor/bin/phpunit --testsuite Integration
```

### Test específico
```bash
vendor/bin/phpunit --filter=test_can_bind_and_resolve_concrete_class
```

### Con coverage (HTML)
```bash
composer test:coverage
```

El reporte se genera en `coverage/index.html`

## Cobertura de Tests

### Core Components

**Container** (`tests/Unit/Core/ContainerTest.php`)
- Binding y resolución de clases
- Singleton vs Transient bindings
- Autowiring de dependencias
- Interface to implementation binding
- Closures como factories
- Parámetros explícitos
- Valores por defecto
- Detección de dependencias circulares
- Method injection con `call()`
- Aliases
- Nullable dependencies

**Router** (`tests/Unit/Routing/RouterTest.php`)
- Registro de rutas (GET, POST, PUT, DELETE, PATCH, OPTIONS, ALL, MULTI)
- Matching de rutas exactas
- Parámetros dinámicos (`:param`)
- Wildcards (`*` y `**`)
- Route groups con prefix y middleware
- Named routes
- Middlewares en rutas
- Normalización de paths
- Orden de matching

### HTTP Components

**Request** (`tests/Unit/Http/RequestTest.php`)
- Captura de método y path
- Query parameters
- Route parameters
- Input (params > post > query)
- Headers (case-insensitive)
- JSON body parsing
- Detección de JSON/AJAX
- Method helpers (isGet, isPost, etc.)

**Response** (`tests/Unit/Http/ResponseTest.php`)
- Static factories (json, html, text, empty, error)
- Status codes
- Headers
- Fluent API / method chaining
- Unicode y slashes en JSON
- Conversión toString

### Middleware

**Pipeline** (`tests/Unit/Middleware/PipelineTest.php`)
- Ejecución sin middlewares
- Ejecución de middlewares en orden
- Short-circuiting
- Auto-conversión array → JSON
- Auto-conversión string → HTML
- JsonSerializable support
- Modificación de request
- Modificación de response
- Instanciación de middlewares por class name

### Configuration

**Config** (`tests/Unit/Config/ConfigTest.php`)
- Dot notation access
- Nested values
- Default values
- Set values
- Lazy loading
- Reload
- Clear cache
- Whole file retrieval

### Integration Tests

**FullStackTest** (`tests/Integration/FullStackTest.php`)
- Complete request lifecycle
- Route parameters en full stack
- Middleware execution
- DI en controladores
- JSON request handling
- Route not found (404)
- Auto-conversión de arrays a JSON

## Escribir Nuevos Tests

### Test Unitario

```php
<?php

namespace PhobosFramework\Tests\Unit\YourComponent;

use PHPUnit\Framework\TestCase;

class YourComponentTest extends TestCase
{
    public function test_your_feature(): void
    {
        // Arrange
        $component = new YourComponent();

        // Act
        $result = $component->doSomething();

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Test de Integración

```php
<?php

namespace PhobosFramework\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Core\Phobos;

class YourIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset Phobos singleton
        $reflection = new \ReflectionClass(Phobos::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function test_feature(): void
    {
        $app = Phobos::init(__DIR__)
            ->bootstrap(YourTestModule::class);

        $request = // ... create request
        $response = $app->run($request);

        // Assertions
    }
}
```

## Convenciones

1. **Nombrado**: `test_describe_what_it_tests` (snake_case)
2. **Arrange-Act-Assert**: Organizar tests en 3 secciones claras
3. **Un concepto por test**: Cada test verifica UNA cosa
4. **Fixtures al final**: Clases de apoyo al final del archivo
5. **Cleanup**: Usar `setUp()` y `tearDown()` para estado limpio

## CI/CD

Los tests están configurados para ejecutarse automáticamente en:
- Pull requests
- Pushes a main/master
- Tags de versión

## Debugging Tests

### Ver output de tests
```bash
vendor/bin/phpunit --testdox
```

### Modo verbose
```bash
vendor/bin/phpunit --verbose
```

### Detener en primer fallo
```bash
vendor/bin/phpunit --stop-on-failure
```

### Con Xdebug coverage
```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text
```

## Métricas

Objetivo de cobertura: **>= 80%**

Ejecutar análisis de cobertura:
```bash
composer test:coverage
```

## Problemas Comunes

### Tests fallan con "Class not found"
```bash
composer dump-autoload
```

### Cache de PHPUnit
```bash
rm -rf .phpunit.cache
```

### Permisos en archivos temporales
Los tests de Config crean archivos en `/tmp`. Asegúrate de tener permisos de escritura.

## Contribuir

Al agregar nueva funcionalidad al framework:

1. Escribe el test primero (TDD)
2. Asegura que los tests existentes sigan pasando
3. Mantén cobertura >= 80%
4. Documenta comportamientos complejos
5. Usa fixtures para setup repetitivo

## Recursos

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Phobos Framework README](../README.md)
