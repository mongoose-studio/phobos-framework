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

namespace PhobosFramework\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Middleware\Pipeline;
use PhobosFramework\Middleware\MiddlewareInterface;
use PhobosFramework\Http\Request;
use PhobosFramework\Http\Response;

/**
 * Clase PipelineTest
 *
 * Esta clase contiene pruebas unitarias para la clase Pipeline. Se encarga de probar
 * diversas funcionalidades del Pipeline, incluyendo su capacidad para procesar
 * solicitudes a través de middlewares, manejar respuestas y verificar la
 * transformación de datos.
 */
class PipelineTest extends TestCase {
    public function test_pipeline_executes_without_middlewares(): void {
        $request = $this->createRequest();
        $pipeline = new Pipeline($request);

        $response = $pipeline
            ->through([])
            ->then(fn($req) => Response::json(['status' => 'ok']));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_pipeline_executes_single_middleware(): void {
        $request = $this->createRequest();
        $middleware = new TestMiddleware('test');
        $pipeline = new Pipeline($request);

        $response = $pipeline
            ->through([$middleware])
            ->then(fn($req) => Response::json(['result' => 'success']));

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('success', $content['result']);
        $this->assertEquals('test-header', $response->getHeaders()['X-Test'] ?? null);
    }

    public function test_pipeline_executes_multiple_middlewares_in_order(): void {
        $request = $this->createRequest();
        $pipeline = new Pipeline($request);

        $response = $pipeline
            ->through([
                new TestMiddleware('first'),
                new TestMiddleware('second'),
            ])
            ->then(fn($req) => Response::json(['result' => 'success']));

        $headers = $response->getHeaders();
        $this->assertEquals('first-header', $headers['X-Test']);
        $this->assertEquals('second-header', $headers['X-Second']);
    }

    public function test_pipeline_can_short_circuit(): void {
        $request = $this->createRequest();
        $pipeline = new Pipeline($request);

        $response = $pipeline
            ->through([
                new ShortCircuitMiddleware(),
                new TestMiddleware('should-not-run'),
            ])
            ->then(fn($req) => Response::json(['result' => 'should-not-reach']));

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('short-circuited', $content['message']);
        $this->assertArrayNotHasKey('X-Test', $response->getHeaders());
    }

    public function test_pipeline_converts_array_to_json(): void {
        $request = $this->createRequest();
        $pipeline = new Pipeline($request);

        $response = $pipeline
            ->through([])
            ->then(fn($req) => ['key' => 'value']);

        $this->assertEquals('application/json; charset=utf-8', $response->getHeaders()['Content-Type']);
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('value', $content['key']);
    }

    public function test_pipeline_converts_string_to_html(): void {
        $request = $this->createRequest();
        $pipeline = new Pipeline($request);

        $response = $pipeline
            ->through([])
            ->then(fn($req) => '<h1>Hello</h1>');

        $this->assertEquals('text/html; charset=utf-8', $response->getHeaders()['Content-Type']);
        $this->assertEquals('<h1>Hello</h1>', $response->getContent());
    }

    public function test_pipeline_passes_response_through(): void {
        $request = $this->createRequest();
        $pipeline = new Pipeline($request);

        $customResponse = Response::json(['custom' => true])->status(201);

        $response = $pipeline
            ->through([])
            ->then(fn($req) => $customResponse);

        $this->assertSame($customResponse, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_pipeline_handles_json_serializable(): void {
        $request = $this->createRequest();
        $pipeline = new Pipeline($request);

        $response = $pipeline
            ->through([])
            ->then(fn($req) => new JsonSerializableObject());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('test', $content['data']);
    }

    public function test_middleware_can_modify_request(): void {
        $request = $this->createRequest();
        $pipeline = new Pipeline($request);

        $response = $pipeline
            ->through([new ModifyRequestMiddleware()])
            ->then(function ($req) {
                return Response::json(['params' => $req->allParams()]);
            });

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('modified', $content['params']['test']);
    }

    public function test_middleware_can_modify_response(): void {
        $request = $this->createRequest();
        $pipeline = new Pipeline($request);

        $response = $pipeline
            ->through([new ModifyResponseMiddleware()])
            ->then(fn($req) => Response::json(['original' => true]));

        $this->assertEquals('modified-value', $response->getHeaders()['X-Modified']);
    }

    public function test_pipeline_instantiates_middleware_by_class_name(): void {
        $request = $this->createRequest();
        $pipeline = new Pipeline($request);

        $response = $pipeline
            ->through([TestMiddleware::class])
            ->then(fn($req) => Response::json(['result' => 'success']));

        // Should work with class name string
        $this->assertEquals(200, $response->getStatusCode());
    }

    private function createRequest(): Request {
        return new Request(
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
    }
}

// Test fixtures

class TestMiddleware implements MiddlewareInterface {
    public function __construct(private string $id = 'default') {
    }

    public function handle(Request $request, \Closure $next): Response {
        $response = $next($request);

        if ($this->id === 'first') {
            $response->header('X-Test', 'first-header');
        } elseif ($this->id === 'second') {
            $response->header('X-Second', 'second-header');
        } else {
            $response->header('X-Test', "{$this->id}-header");
        }

        return $response;
    }
}

class ShortCircuitMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): Response {
        // Don't call $next, short-circuit the pipeline
        return Response::json(['message' => 'short-circuited']);
    }
}

class ModifyRequestMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): Response {
        $request->setParams(['test' => 'modified']);
        return $next($request);
    }
}

class ModifyResponseMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): Response {
        $response = $next($request);
        $response->header('X-Modified', 'modified-value');
        return $response;
    }
}

class JsonSerializableObject implements \JsonSerializable {
    public function jsonSerialize(): mixed {
        return ['data' => 'test'];
    }
}
