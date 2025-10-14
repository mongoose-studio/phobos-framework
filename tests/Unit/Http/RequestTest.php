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

namespace PhobosFramework\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Http\Request;

/**
 * Class RequestTest
 *
 * Pruebas unitarias para la clase Request, responsable de validar varias
 * funcionalidades incluyendo métodos HTTP, cabeceras, parámetros de consulta,
 * parámetros de ruta y cuerpos de solicitud.
 */
class RequestTest extends TestCase {
    public function test_can_get_method(): void {
        $request = $this->createRequest('POST');

        $this->assertEquals('POST', $request->method());
    }

    public function test_can_get_path(): void {
        $request = $this->createRequest('GET', '/users/123');

        $this->assertEquals('/users/123', $request->path());
    }

    public function test_can_get_query_parameter(): void {
        $request = $this->createRequest('GET', '/users', ['page' => '1']);

        $this->assertEquals('1', $request->query('page'));
    }

    public function test_query_returns_all_parameters_when_no_key(): void {
        $request = $this->createRequest('GET', '/users', ['page' => '1', 'limit' => '10']);

        $this->assertEquals(['page' => '1', 'limit' => '10'], $request->query());
    }

    public function test_query_returns_default_for_missing_key(): void {
        $request = $this->createRequest('GET', '/users');

        $this->assertEquals('default', $request->query('page', 'default'));
    }

    public function test_can_set_and_get_route_params(): void {
        $request = $this->createRequest('GET', '/users/123');
        $request->setParams(['id' => '123']);

        $this->assertEquals('123', $request->param('id'));
    }

    public function test_all_params_returns_all_route_params(): void {
        $request = $this->createRequest('GET', '/users/123');
        $request->setParams(['id' => '123', 'slug' => 'test']);

        $this->assertEquals(['id' => '123', 'slug' => 'test'], $request->allParams());
    }

    public function test_input_checks_params_then_post_then_query(): void {
        $request = new Request(
            method: 'POST',
            path: '/users',
            query: ['name' => 'query'],
            post: ['name' => 'post'],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: ''
        );
        $request->setParams(['name' => 'param']);

        // Should prefer route param
        $this->assertEquals('param', $request->input('name'));
    }

    public function test_input_falls_back_to_post(): void {
        $request = new Request(
            method: 'POST',
            path: '/users',
            query: ['name' => 'query'],
            post: ['email' => 'test@example.com'],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: ''
        );

        $this->assertEquals('test@example.com', $request->input('email'));
    }

    public function test_can_get_header(): void {
        $request = $this->createRequest('GET', '/', [], ['Authorization' => 'Bearer token']);

        $this->assertEquals('Bearer token', $request->header('Authorization'));
    }

    public function test_header_is_case_insensitive(): void {
        $request = $this->createRequest('GET', '/', [], ['Content-Type' => 'application/json']);

        $this->assertEquals('application/json', $request->header('CONTENT-TYPE'));
        $this->assertEquals('application/json', $request->header('content-type'));
    }

    public function test_has_header_returns_true_when_exists(): void {
        $request = $this->createRequest('GET', '/', [], ['X-Custom' => 'value']);

        $this->assertTrue($request->hasHeader('X-Custom'));
    }

    public function test_has_header_returns_false_when_missing(): void {
        $request = $this->createRequest('GET', '/');

        $this->assertFalse($request->hasHeader('X-Custom'));
    }

    public function test_can_get_all_headers(): void {
        $headers = ['Authorization' => 'Bearer token', 'Content-Type' => 'application/json'];
        $request = $this->createRequest('GET', '/', [], $headers);

        $this->assertEquals($headers, $request->headers());
    }

    public function test_can_parse_json_body(): void {
        $body = json_encode(['name' => 'John', 'email' => 'john@example.com']);
        $request = new Request(
            method: 'POST',
            path: '/users',
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: $body
        );

        $this->assertEquals('John', $request->json('name'));
        $this->assertEquals('john@example.com', $request->json('email'));
    }

    public function test_json_returns_all_data_when_no_key(): void {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $body = json_encode($data);
        $request = new Request(
            method: 'POST',
            path: '/users',
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: $body
        );

        $json = $request->json();
        $this->assertEquals('John', $json->name);
        $this->assertEquals('john@example.com', $json->email);
    }

    public function test_is_json_returns_true_for_json_content_type(): void {
        $request = $this->createRequest('POST', '/', [], ['Content-Type' => 'application/json']);

        $this->assertTrue($request->isJson());
    }

    public function test_is_json_returns_false_for_other_content_types(): void {
        $request = $this->createRequest('POST', '/', [], ['Content-Type' => 'text/html']);

        $this->assertFalse($request->isJson());
    }

    public function test_is_method_checks(): void {
        $getRequest = $this->createRequest('GET');
        $postRequest = $this->createRequest('POST');

        $this->assertTrue($getRequest->isGet());
        $this->assertFalse($getRequest->isPost());
        $this->assertTrue($postRequest->isPost());
        $this->assertFalse($postRequest->isGet());
    }

    public function test_is_ajax_returns_true_with_xmlhttprequest_header(): void {
        $request = $this->createRequest('GET', '/', [], ['X-Requested-With' => 'XMLHttpRequest']);

        $this->assertTrue($request->isAjax());
    }

    public function test_is_ajax_returns_false_without_header(): void {
        $request = $this->createRequest('GET', '/');

        $this->assertFalse($request->isAjax());
    }

    public function test_can_get_body(): void {
        $body = 'raw body content';
        $request = new Request(
            method: 'POST',
            path: '/users',
            query: [],
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: [],
            body: $body
        );

        $this->assertEquals($body, $request->body());
    }

    public function test_all_merges_query_and_params(): void {
        $request = $this->createRequest('GET', '/users', ['page' => '1']);
        $request->setParams(['id' => '123']);

        $all = $request->all();

        $this->assertEquals('1', $all['page']);
        $this->assertEquals('123', $all['id']);
    }

    private function createRequest(
        string $method = 'GET',
        string $path = '/',
        array  $query = [],
        array  $headers = []
    ): Request {
        return new Request(
            method: $method,
            path: $path,
            query: $query,
            post: [],
            files: [],
            cookies: [],
            server: [],
            headers: $headers,
            body: ''
        );
    }
}
