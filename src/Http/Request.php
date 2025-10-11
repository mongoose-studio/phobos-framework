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

namespace PhobosFramework\Http;

use stdClass;

class Request {

    private array $params = [];
    private ?stdClass $jsonCache = null;

    public function __construct(
        private string $method,
        private string $path,
        private array  $query,
        private array  $post,
        private array  $files,
        private array  $cookies,
        private array  $server,
        private array  $headers,
        private string $body,
    ) {
    }

    /**
     * Crear Request desde globals
     */
    public static function capture(): self {
        return new self(
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            path: parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
            query: $_GET,
            post: $_POST,
            files: $_FILES,
            cookies: $_COOKIE,
            server: $_SERVER,
            headers: self::extractHeaders(),
            body: file_get_contents('php://input') ?: '',
        );
    }

    /**
     * Extraer headers del request
     */
    private static function extractHeaders(): array {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }

        // Headers especiales
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }

    /**
     * Obtener método HTTP
     */
    public function method(): string {
        return $this->method;
    }

    /**
     * Obtener path
     */
    public function path(): string {
        return $this->path;
    }

    /**
     * Obtener query parameters
     */
    public function query(string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * Obtener parámetro de ruta
     */
    public function param(string $key, mixed $default = null): mixed {
        return $this->params[$key] ?? $default;
    }

    /**
     * Establecer parámetros de ruta
     */
    public function setParams(array $params): void {
        $this->params = $params;
    }

    /**
     * Obtener todos los parámetros (query + route params)
     */
    public function all(): array {
        return array_merge($this->query, $this->params);
    }

    /**
     * Obtener input (query, post, route params)
     */
    public function input(string $key, mixed $default = null): mixed {
        return $this->params[$key]
            ?? $this->post[$key]
            ?? $this->query[$key]
            ?? $default;
    }

    /**
     * Obtener header
     */
    public function header(string $name, mixed $default = null): mixed {
        $name = str_replace('_', '-', strtoupper($name));

        foreach ($this->headers as $key => $value) {
            if (strtoupper($key) === $name) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Verificar si tiene header
     */
    public function hasHeader(string $name): bool {
        return $this->header($name) !== null;
    }

    /**
     * Obtener todos los headers
     */
    public function headers(): array {
        return $this->headers;
    }

    /**
     * Obtener body raw
     */
    public function body(): string {
        return $this->body;
    }

    /**
     * Parsear JSON body
     */
    public function json(string $key = null, mixed $default = null): mixed {
        if ($this->jsonCache === null) {
            $this->jsonCache = json_decode($this->body) ?? [];
        }

        if ($key === null) {
            return $this->jsonCache;
        }

        return $this->jsonCache->{$key} ?? $default;
    }

    /**
     * Verificar si es JSON request
     */
    public function isJson(): bool {
        $contentType = $this->header('Content-Type', '');
        return str_contains($contentType, 'application/json');
    }

    /**
     * Obtener IP del cliente
     */
    public function ip(): string {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Obtener User-Agent
     */
    public function userAgent(): string {
        return $this->header('User-Agent', '');
    }

    /**
     * Verificar método
     */
    public function isMethod(string $method): bool {
        return strtoupper($this->method) === strtoupper($method);
    }

    public function isGet(): bool {
        return $this->isMethod('GET');
    }

    public function isPost(): bool {
        return $this->isMethod('POST');
    }

    public function isPut(): bool {
        return $this->isMethod('PUT');
    }

    public function isDelete(): bool {
        return $this->isMethod('DELETE');
    }

    public function isPatch(): bool {
        return $this->isMethod('PATCH');
    }

    /**
     * Verificar si es AJAX
     */
    public function isAjax(): bool {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    /**
     * Obtener archivo subido
     */
    public function file(string $key): ?array {
        return $this->files[$key] ?? null;
    }

    /**
     * Obtener cookie
     */
    public function cookie(string $key, mixed $default = null): mixed {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Obtener valor del server
     */
    public function server(string $key, mixed $default = null): mixed {
        return $this->server[$key] ?? $default;
    }
}
