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
use PhobosFramework\Http\HttpStatus;
use PhobosFramework\Http\Response;

/**
 * Class ResponseTest
 *
 * Esta clase contiene múltiples métodos de prueba para asegurar el funcionamiento correcto
 * de la clase Response. Prueba varios tipos de respuestas, cabeceras, códigos de estado
 * y encadenamiento de métodos.
 */
class ResponseTest extends TestCase {
    public function test_can_create_json_response(): void {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $response = Response::json($data);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $response->getContent());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeaders()['Content-Type']);
    }

    public function test_can_create_json_response_with_custom_status(): void {
        $response = Response::json(['error' => 'Not found'], 404);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_can_create_html_response(): void {
        $html = '<h1>Hello World</h1>';
        $response = Response::html($html);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($html, $response->getContent());
        $this->assertEquals('text/html; charset=utf-8', $response->getHeaders()['Content-Type']);
    }

    public function test_can_create_text_response(): void {
        $text = 'Plain text content';
        $response = Response::text($text);

        $this->assertEquals($text, $response->getContent());
        $this->assertEquals('text/plain; charset=utf-8', $response->getHeaders()['Content-Type']);
    }

    public function test_can_create_empty_response(): void {
        $response = Response::empty();

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
    }

    public function test_can_create_error_response(): void {
        $response = Response::error('Something went wrong', 500);

        $this->assertEquals(500, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Something went wrong', $content['message']);
        $this->assertArrayHasKey('error', $content);
    }

    public function test_can_add_header(): void {
        $response = new Response('content');
        $response->header('X-Custom-Header', 'custom-value');

        $this->assertEquals('custom-value', $response->getHeaders()['X-Custom-Header']);
    }

    public function test_can_add_multiple_headers(): void {
        $response = new Response('content');
        $response->withHeaders([
            'X-Header-1' => 'value1',
            'X-Header-2' => 'value2',
        ]);

        $headers = $response->getHeaders();
        $this->assertEquals('value1', $headers['X-Header-1']);
        $this->assertEquals('value2', $headers['X-Header-2']);
    }

    public function test_can_set_status_code(): void {
        $response = new Response('content');
        $response->status(404);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_header_returns_self_for_chaining(): void {
        $response = new Response('content');
        $result = $response->header('X-Custom', 'value');

        $this->assertSame($response, $result);
    }

    public function test_status_returns_self_for_chaining(): void {
        $response = new Response('content');
        $result = $response->status(404);

        $this->assertSame($response, $result);
    }

    public function test_can_chain_methods(): void {
        $response = (new Response('content'))
            ->status(201)
            ->header('X-Custom', 'value')
            ->withHeaders(['X-Another' => 'another']);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('value', $response->getHeaders()['X-Custom']);
        $this->assertEquals('another', $response->getHeaders()['X-Another']);
    }

    public function test_to_string_converts_content(): void {
        $response = new Response('test content');

        $this->assertEquals('test content', (string)$response);
    }

    public function test_to_string_converts_array_to_json(): void {
        $data = ['key' => 'value'];
        $response = new Response($data);

        $this->assertEquals(json_encode($data), (string)$response);
    }

    public function test_json_handles_unicode_characters(): void {
        $data = ['name' => 'José', 'city' => 'São Paulo'];
        $response = Response::json($data);

        $content = $response->getContent();
        $this->assertStringContainsString('José', $content);
        $this->assertStringContainsString('São Paulo', $content);
    }

    public function test_json_handles_slashes(): void {
        $data = ['url' => 'https://example.com/path'];
        $response = Response::json($data);

        $content = $response->getContent();
        $this->assertStringContainsString('https://example.com/path', $content);
        $this->assertStringNotContainsString('\/', $content);
    }

    public function test_default_status_code_is_200(): void {
        $response = new Response('content');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_can_pass_headers_in_constructor(): void {
        $response = new Response('content', 200, ['X-Custom' => 'value']);

        $this->assertEquals('value', $response->getHeaders()['X-Custom']);
    }

    /**
     * Regresión: el constructor usaba intval() sobre el enum, lo que devolvía 1
     * y producía un status HTTP inválido con respuesta vacía.
     */
    public function test_constructor_accepts_http_status_enum(): void {
        $response = new Response('content', HttpStatus::CREATED);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_json_accepts_http_status_enum(): void {
        $response = Response::json(['ok' => true], HttpStatus::CREATED);

        $this->assertSame(201, $response->getStatusCode());
    }

    /**
     * Regresión: error() pasaba el enum a getStatusText(int), lo que lanzaba un TypeError.
     */
    public function test_error_accepts_http_status_enum(): void {
        $response = Response::error('Not found', HttpStatus::NOT_FOUND);

        $this->assertSame(404, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Not Found', $content['error']);
        $this->assertEquals('Not found', $content['message']);
    }

    public function test_empty_accepts_http_status_enum(): void {
        $response = Response::empty(HttpStatus::NO_CONTENT);

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_text_accepts_http_status_enum(): void {
        $response = Response::text('gone', HttpStatus::GONE);

        $this->assertSame(410, $response->getStatusCode());
    }

    public function test_html_accepts_http_status_enum(): void {
        $response = Response::html('<h1>Oops</h1>', HttpStatus::INTERNAL_SERVER_ERROR);

        $this->assertSame(500, $response->getStatusCode());
    }

    public function test_status_accepts_http_status_enum(): void {
        $response = (new Response('content'))->status(HttpStatus::ACCEPTED);

        $this->assertSame(202, $response->getStatusCode());
    }

    /**
     * Regresión: con un content array y un Content-Type explícito, send() hacía
     * `echo $array` ("Array to string conversion") en vez de serializarlo.
     */
    public function test_send_serializes_array_content_with_explicit_content_type(): void {
        $response = new Response(
            ['key' => 'value'],
            200,
            ['Content-Type' => 'application/vnd.api+json']
        );

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertEquals('{"key":"value"}', $output);
    }

    public function test_send_serializes_array_content_without_content_type(): void {
        $response = new Response(['key' => 'value']);

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertEquals('{"key":"value"}', $output);
    }

    public function test_send_outputs_string_content_as_is(): void {
        $response = Response::html('<h1>Hola</h1>');

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertEquals('<h1>Hola</h1>', $output);
    }
}
