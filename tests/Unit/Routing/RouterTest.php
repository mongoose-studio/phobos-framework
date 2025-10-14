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

namespace PhobosFramework\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Routing\Router;
use PhobosFramework\Http\Request;
use PhobosFramework\Http\Response;
use PhobosFramework\Exceptions\NotFoundException;

/**
 * Class RouterTest
 *
 * Esta clase contiene múltiples pruebas para asegurar el funcionamiento correcto
 * del enrutador (Router). Verifica el registro de rutas, coincidencia de patrones,
 * manejo de parámetros, grupos de rutas, middlewares y generación de URLs.
 */
class RouterTest extends TestCase {
    private Router $router;

    protected function setUp(): void {
        $this->router = new Router();
    }

    public function test_can_register_get_route(): void {
        $this->router->get('/users', fn() => 'users');

        $routes = $this->router->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertEquals('/users', $routes[0]->getPath());
        $this->assertEquals(['GET'], $routes[0]->getMethods());
    }

    public function test_can_register_post_route(): void {
        $this->router->post('/users', fn() => 'create');

        $routes = $this->router->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertEquals(['POST'], $routes[0]->getMethods());
    }

    public function test_can_register_multiple_methods(): void {
        $this->router->multi(['GET', 'POST'], '/users', fn() => 'users');

        $routes = $this->router->getRoutes();

        $this->assertEquals(['GET', 'POST'], $routes[0]->getMethods());
    }

    public function test_can_register_all_methods(): void {
        $this->router->all('/users', fn() => 'users');

        $routes = $this->router->getRoutes();

        $this->assertContains('GET', $routes[0]->getMethods());
        $this->assertContains('POST', $routes[0]->getMethods());
        $this->assertContains('PUT', $routes[0]->getMethods());
        $this->assertContains('DELETE', $routes[0]->getMethods());
    }

    public function test_matches_exact_path(): void {
        $this->router->get('/users', fn() => 'users');

        $request = $this->createRequest('GET', '/users');
        $match = $this->router->match($request);

        $this->assertNotNull($match);
        $this->assertEquals('/users', $match->getRoute()->getPath());
    }

    public function test_matches_route_with_parameter(): void {
        $this->router->get('/users/:id', fn() => 'user');

        $request = $this->createRequest('GET', '/users/123');
        $match = $this->router->match($request);

        $this->assertNotNull($match);
        $this->assertEquals('123', $match->getParams()['id']);
    }

    public function test_matches_route_with_multiple_parameters(): void {
        $this->router->get('/users/:userId/posts/:postId', fn() => 'post');

        $request = $this->createRequest('GET', '/users/10/posts/20');
        $match = $this->router->match($request);

        $this->assertEquals('10', $match->getParams()['userId']);
        $this->assertEquals('20', $match->getParams()['postId']);
    }

    public function test_matches_wildcard_single_segment(): void {
        $this->router->get('/files/*/download', fn() => 'download');

        $request = $this->createRequest('GET', '/files/document/download');
        $match = $this->router->match($request);

        $this->assertNotNull($match);
        $this->assertEquals('document', $match->getParams()['segment_0']);
    }

    public function test_matches_wildcard_multiple_segments(): void {
        $this->router->get('/docs/**', fn() => 'docs');

        $request = $this->createRequest('GET', '/docs/api/v1/users');
        $match = $this->router->match($request);

        $this->assertNotNull($match);
        $this->assertEquals('api/v1/users', $match->getParams()['wildcard']);
    }

    public function test_throws_not_found_for_unmatched_path(): void {
        $this->router->get('/users', fn() => 'users');

        $this->expectException(NotFoundException::class);

        $request = $this->createRequest('GET', '/posts');
        $this->router->match($request);
    }

    public function test_throws_not_found_for_wrong_method(): void {
        $this->router->get('/users', fn() => 'users');

        $this->expectException(NotFoundException::class);

        $request = $this->createRequest('POST', '/users');
        $this->router->match($request);
    }

    public function test_can_add_middleware_to_route(): void {
        $this->router->get('/users', fn() => 'users')
            ->middleware('auth');

        $routes = $this->router->getRoutes();

        $this->assertEquals(['auth'], $routes[0]->getMiddlewares());
    }

    public function test_can_add_multiple_middlewares_to_route(): void {
        $this->router->get('/users', fn() => 'users')
            ->middleware(['auth', 'admin']);

        $routes = $this->router->getRoutes();

        $this->assertEquals(['auth', 'admin'], $routes[0]->getMiddlewares());
    }

    public function test_can_name_route(): void {
        $this->router->get('/users/:id', fn() => 'user')
            ->name('users.show');

        $url = $this->router->route('users.show', ['id' => 123]);

        $this->assertEquals('/users/123', $url);
    }

    public function test_throws_exception_for_missing_route_parameters(): void {
        $this->router->get('/users/:id', fn() => 'user')
            ->name('users.show');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing parameters');

        $this->router->route('users.show', []);
    }

    public function test_throws_exception_for_duplicate_route_name(): void {
        $this->router->get('/users', fn() => 'users')
            ->name('users');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already registered');

        $this->router->get('/posts', fn() => 'posts')
            ->name('users');
    }

    public function test_can_group_routes_with_prefix(): void {
        $this->router->group(['prefix' => 'api'], function (Router $r) {
            $r->get('/users', fn() => 'users');
            $r->get('/posts', fn() => 'posts');
        });

        $routes = $this->router->getRoutes();

        $this->assertEquals('/api/users', $routes[0]->getPath());
        $this->assertEquals('/api/posts', $routes[1]->getPath());
    }

    public function test_can_group_routes_with_middleware(): void {
        $this->router->group(['middleware' => 'auth'], function (Router $r) {
            $r->get('/users', fn() => 'users');
        });

        $routes = $this->router->getRoutes();

        $this->assertEquals(['auth'], $routes[0]->getMiddlewares());
    }

    public function test_can_nest_route_groups(): void {
        $this->router->group(['prefix' => 'api'], function (Router $r) {
            $r->group(['prefix' => 'v1'], function (Router $r) {
                $r->get('/users', fn() => 'users');
            });
        });

        $routes = $this->router->getRoutes();

        $this->assertEquals('/api/v1/users', $routes[0]->getPath());
    }

    public function test_nested_groups_merge_middlewares(): void {
        $this->router->group(['middleware' => 'auth'], function (Router $r) {
            $r->group(['middleware' => 'admin'], function (Router $r) {
                $r->get('/users', fn() => 'users');
            });
        });

        $routes = $this->router->getRoutes();

        $this->assertEquals(['auth', 'admin'], $routes[0]->getMiddlewares());
    }

    public function test_normalizes_paths_with_trailing_slash(): void {
        $this->router->get('/users/', fn() => 'users');

        $routes = $this->router->getRoutes();

        $this->assertEquals('/users', $routes[0]->getPath());
    }

    public function test_root_path_remains_slash(): void {
        $this->router->get('/', fn() => 'home');

        $routes = $this->router->getRoutes();

        $this->assertEquals('/', $routes[0]->getPath());
    }

    public function test_matches_routes_in_order_of_registration(): void {
        $this->router->get('/users/*', fn() => 'wildcard');
        $this->router->get('/users/:id', fn() => 'specific');

        $request = $this->createRequest('GET', '/users/123');
        $match = $this->router->match($request);

        // Should match first registered route
        $this->assertEquals('/users/*', $match->getRoute()->getPath());
    }

    private function createRequest(string $method, string $path): Request {
        return new Request(
            method: $method,
            path: $path,
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: ''
        );
    }
}
