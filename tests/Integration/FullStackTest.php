<?php

/**
 * # Phobos Framework
 *
 * Para la información completa acerca del copyright y la licencia,
 * por favor vea el archivo LICENSE que va distribuido con el código fuente.
 *
 * @author      Marcel Rojas <marcelrojas16@gmail.com>
 * @copyright   Copyright (c) 2012-2025, Marcel Rojas <marcelrojas16@gmail.com>
 */

namespace PhobosFramework\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Core\Phobos;
use PhobosFramework\Core\Container;
use PhobosFramework\Routing\Router;
use PhobosFramework\Http\Request;
use PhobosFramework\Http\Response;
use PhobosFramework\Middleware\MiddlewareInterface;
use PhobosFramework\Module\ModuleInterface;
use ReflectionClass;

/**
 * Pruebas de Integración Full Stack
 *
 * Estas pruebas verifican el ciclo de vida completo de solicitud-respuesta
 * del Framework Phobos, probando todos los componentes trabajando juntos.
 */
class FullStackTest extends TestCase {
    protected function setUp(): void {
        // Reset singleton between tests
        $reflection = new ReflectionClass(Phobos::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function test_complete_request_lifecycle(): void {
        $app = Phobos::init(__DIR__)
            ->bootstrap(TestAppModule::class);

        $request = new Request(
            method: 'GET',
            path: '/test',
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: ''
        );

        $response = $app->run($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_route_with_parameters(): void {
        $app = Phobos::init(__DIR__)
            ->bootstrap(TestAppModule::class);

        $request = new Request(
            method: 'GET',
            path: '/users/123',
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: ''
        );

        $response = $app->run($request);
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('123', $content['user_id']);
    }

    public function test_middleware_execution(): void {
        $app = Phobos::init(__DIR__)
            ->bootstrap(TestAppModule::class);

        $request = new Request(
            method: 'GET',
            path: '/protected',
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: ''
        );

        $response = $app->run($request);

        $this->assertEquals('test-middleware', $response->getHeaders()['X-Middleware']);
    }

    public function test_dependency_injection_in_controllers(): void {
        $app = Phobos::init(__DIR__)
            ->bootstrap(TestAppModule::class);

        $request = new Request(
            method: 'GET',
            path: '/di-test',
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: ''
        );

        $response = $app->run($request);
        $content = json_decode($response->getContent(), true);

        $this->assertEquals('TestService', $content['service']);
    }

    public function test_json_request_handling(): void {
        $app = Phobos::init(__DIR__)
            ->bootstrap(TestAppModule::class);

        $jsonData = ['name' => 'John', 'email' => 'john@example.com'];
        $request = new Request(
            method: 'POST',
            path: '/users',
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: ['Content-Type' => 'application/json'],
            body: json_encode($jsonData)
        );

        $response = $app->run($request);
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('John', $content['name']);
    }

    public function test_route_not_found(): void {
        $app = Phobos::init(__DIR__)
            ->bootstrap(TestAppModule::class);

        $request = new Request(
            method: 'GET',
            path: '/nonexistent',
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: ''
        );

        $this->expectException(\PhobosFramework\Exceptions\NotFoundException::class);

        $app->run($request);
    }

    public function test_array_return_converts_to_json(): void {
        $app = Phobos::init(__DIR__)
            ->bootstrap(TestAppModule::class);

        $request = new Request(
            method: 'GET',
            path: '/array-response',
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: ''
        );

        $response = $app->run($request);

        $this->assertEquals('application/json; charset=utf-8', $response->getHeaders()['Content-Type']);
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('test', $content['data']);
    }
}

// Test Fixtures

class TestAppModule implements ModuleInterface {
    public function routes(Router $router): void {
        $router->get('/test', fn() => Response::json(['status' => 'ok']));

        $router->get('/users/:id', fn(Request $request, string $id) => Response::json(['user_id' => $id])
        );

        $router->get('/protected', fn() => Response::json(['protected' => true]))
            ->middleware(TestMiddleware::class);

        $router->get('/di-test', [TestController::class, 'index']);

        $router->post('/users', function (Request $request) {
            $name = $request->json('name');
            return Response::json(['name' => $name], 201);
        });

        $router->get('/array-response', fn() => ['data' => 'test']);
    }

    public function middlewares(): array {
        return [];
    }

    public function providers(): array {
        return [TestServiceProvider::class];
    }
}

class TestMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): Response {
        $response = $next($request);
        $response->header('X-Middleware', 'test-middleware');
        return $response;
    }
}

class TestController {
    public function __construct(private TestService $service) {
    }

    public function index(): Response {
        return Response::json(['service' => $this->service->getName()]);
    }
}

class TestService {
    public function getName(): string {
        return 'TestService';
    }
}

class TestServiceProvider {
    public function register(Container $container): void {
        $container->singleton(TestService::class);
    }

    public function boot(Container $container): void {
        // Boot logic if needed
    }
}
