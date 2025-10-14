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


/**
 * Clase Request
 *
 * Esta clase maneja las solicitudes HTTP entrantes y proporciona una interfaz limpia
 * para acceder a los datos de la solicitud como parámetros, headers, archivos, cookies, etc.
 *
 * Permite acceder a:
 * - Método HTTP (GET, POST, etc.)
 * - Path de la URL
 * - Parámetros de query
 * - Parámetros de ruta
 * - Headers HTTP
 * - Cuerpo de la solicitud
 * - Archivos subidos
 * - Cookies
 * - Variables del servidor
 */
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
     * Captura la solicitud HTTP actual desde las variables globales de PHP
     *
     * Crea una nueva instancia de Request utilizando los datos de:
     * $_SERVER, $_GET, $_POST, $_FILES, $_COOKIE
     *
     * @return self Nueva instancia de Request con los datos de la solicitud actual
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
     * Extrae los headers HTTP de las variables del servidor
     *
     * Procesa las variables $_SERVER que empiezan con 'HTTP_' y las convierte
     * al formato estándar de headers HTTP
     *
     * @return array Array asociativo con los headers HTTP
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
     * Obtiene el método HTTP de la solicitud
     *
     * @return string Método HTTP (GET, POST, PUT, DELETE, etc.)
     */
    public function method(): string {
        return $this->method;
    }

    /**
     * Obtiene la ruta de la URL de la solicitud
     *
     * @return string Ruta de la URL sin query string
     */
    public function path(): string {
        return $this->path;
    }

    /**
     * Obtiene parámetros del query string
     *
     * @param string|null $key Nombre del parámetro (opcional)
     * @param mixed|null $default Valor por defecto si no existe el parámetro
     * @return mixed Valor del parámetro o array completo si no se especifica key
     */
    public function query(string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * Obtiene un parámetro de la ruta
     *
     * @param string $key Nombre del parámetro
     * @param mixed|null $default Valor por defecto si no existe el parámetro
     * @return mixed Valor del parámetro o valor por defecto
     */
    public function param(string $key, mixed $default = null): mixed {
        return $this->params[$key] ?? $default;
    }

    /**
     * Obtiene todos los parámetros de ruta definidos
     *
     * Devuelve un array con todos los parámetros de ruta que han sido
     * establecidos previamente mediante setParams()
     *
     * @return array Array asociativo con los parámetros de ruta
     */
    public function allParams(): array {
        return $this->params;
    }

    /**
     * Establece los parámetros de ruta para la solicitud
     *
     * Este método permite establecer los parámetros extraídos de la ruta URL
     * que serán utilizados durante el procesamiento de la solicitud.
     *
     * @param array $params Array asociativo con los parámetros de ruta
     */
    public function setParams(array $params): void {
        $this->params = $params;
    }

    /**
     * Obtiene todos los parámetros combinados de query string y parámetros de ruta
     *
     * @return array Array asociativo con todos los parámetros
     */
    public function all(): array {
        return array_merge($this->query, $this->params);
    }

    /**
     * Obtiene un valor de entrada desde query string, post data o parámetros de ruta
     *
     * @param string $key Nombre del parámetro a buscar
     * @param mixed|null $default Valor por defecto si no se encuentra el parámetro
     * @return mixed Valor del parámetro o valor por defecto
     */
    public function input(string $key, mixed $default = null): mixed {
        return $this->params[$key]
            ?? $this->post[$key]
            ?? $this->query[$key]
            ?? $default;
    }

    /**
     * Obtiene el valor de un header HTTP
     *
     * @param string $name Nombre del header
     * @param mixed|null $default Valor por defecto si no existe el header
     * @return mixed Valor del header o valor por defecto
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
     * Verifica si existe un header HTTP específico en la solicitud
     *
     * @param string $name Nombre del header a verificar
     * @return bool True si existe el header, false en caso contrario
     */
    public function hasHeader(string $name): bool {
        return $this->header($name) !== null;
    }

    /**
     * Obtiene todos los headers HTTP de la solicitud
     *
     * @return array Array asociativo con todos los headers
     */
    public function headers(): array {
        return $this->headers;
    }

    /**
     * Obtiene el cuerpo raw de la solicitud HTTP
     *
     * @return string Contenido del cuerpo de la solicitud
     */
    public function body(): string {
        return $this->body;
    }

    /**
     * Obtiene y parsea el cuerpo JSON de la solicitud
     *
     * @param string|null $key Clave a obtener del objeto JSON (opcional)
     * @param mixed|null $default Valor por defecto si no existe la clave
     * @return mixed Objeto JSON completo o valor específico si se proporciona key
     */
    public function json(string $key = null, mixed $default = null): mixed {
        if ($this->jsonCache === null) {
            $this->jsonCache = json_decode($this->body) ?? new stdClass();
        }

        if ($key === null) {
            return $this->jsonCache;
        }

        return $this->jsonCache->{$key} ?? $default;
    }

    /**
     * Verifica si la solicitud contiene contenido JSON
     *
     * @return bool True si es una solicitud JSON, false en caso contrario
     */
    public function isJson(): bool {
        $contentType = $this->header('Content-Type', '');
        return str_contains($contentType, 'application/json');
    }

    /**
     * Obtiene la dirección IP del cliente que realiza la solicitud
     *
     * @return string Dirección IP del cliente
     */
    public function ip(): string {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Obtiene el User-Agent del cliente que realiza la solicitud
     *
     * @return string User-Agent del cliente
     */
    public function userAgent(): string {
        return $this->header('User-Agent', '');
    }

    /**
     * Verifica si la solicitud utiliza un método HTTP específico
     *
     * @param string $method Método HTTP a verificar
     * @return bool True si coincide el método, false en caso contrario
     */
    public function isMethod(string $method): bool {
        return strtoupper($this->method) === strtoupper($method);
    }

    /**
     * Verifica si la solicitud HTTP utiliza el método GET
     *
     * @return bool True si es método GET, false en caso contrario
     */
    public function isGet(): bool {
        return $this->isMethod('GET');
    }

    /**
     * Verifica si la solicitud HTTP utiliza el método POST
     *
     * @return bool True si es método POST, false en caso contrario
     */
    public function isPost(): bool {
        return $this->isMethod('POST');
    }

    /**
     * Verifica si la solicitud HTTP utiliza el método PUT
     *
     * @return bool True si es método PUT, false en caso contrario
     */
    public function isPut(): bool {
        return $this->isMethod('PUT');
    }

    /**
     * Verifica si la solicitud HTTP utiliza el método DELETE
     *
     * @return bool True si es método DELETE, false en caso contrario
     */
    public function isDelete(): bool {
        return $this->isMethod('DELETE');
    }

    /**
     * Verifica si la solicitud HTTP utiliza el método PATCH
     *
     * @return bool True si es método PATCH, false en caso contrario
     */
    public function isPatch(): bool {
        return $this->isMethod('PATCH');
    }

    /**
     * Verifica si la solicitud es una petición AJAX/XHR
     *
     * @return bool True si es una solicitud AJAX, false en caso contrario
     */
    public function isAjax(): bool {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    /**
     * Obtiene información sobre un archivo subido en la solicitud
     *
     * @param string $key Nombre del campo del archivo
     * @return array|null Array con información del archivo o null si no existe
     */
    public function file(string $key): ?array {
        return $this->files[$key] ?? null;
    }

    /**
     * Obtiene el valor de una cookie de la solicitud
     *
     * @param string $key Nombre de la cookie
     * @param mixed|null $default Valor por defecto si no existe la cookie
     * @return mixed Valor de la cookie o valor por defecto
     */
    public function cookie(string $key, mixed $default = null): mixed {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Obtiene un valor de las variables del servidor
     *
     * @param string $key Nombre de la variable del servidor
     * @param mixed|null $default Valor por defecto si no existe la variable
     * @return mixed Valor de la variable del servidor o valor por defecto
     */
    public function server(string $key, mixed $default = null): mixed {
        return $this->server[$key] ?? $default;
    }
}
