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


/**
 * Clase para manejar respuestas HTTP
 *
 * Esta clase proporciona métodos para crear y enviar diferentes tipos de respuestas HTTP,
 * incluyendo JSON, texto plano, HTML y respuestas de error.
 */
class Response {

    private array $headers = [];
    private int $statusCode;
    private mixed $content;

    /**
     * Constructor de la clase Response
     *
     * @param mixed $content Contenido de la respuesta
     * @param int $statusCode Código de estado HTTP (por defecto 200)
     * @param array $headers Headers HTTP adicionales
     */
    public function __construct(
        mixed $content = '',
        int $statusCode = 200,
        array $headers = []
    ) {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Crea una respuesta JSON
     *
     * @param mixed $data Datos a convertir a JSON
     * @param int $statusCode Código de estado HTTP (por defecto 200)
     * @param array $headers Headers HTTP adicionales
     * @return self            Instancia de Response
     */
    public static function json(mixed $data, int $statusCode = 200, array $headers = []): self {
        $response = new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $statusCode,
            $headers
        );

        $response->header('Content-Type', 'application/json; charset=utf-8');

        return $response;
    }

    /**
     * Crea una respuesta vacía
     *
     * @param int $statusCode Código de estado HTTP (por defecto 204)
     * @return self            Instancia de Response
     */
    public static function empty(int $statusCode = 204): self {
        return new self('', $statusCode);
    }

    /**
     * Crea una respuesta de texto plano
     *
     * @param string $content Contenido de texto
     * @param int $statusCode Código de estado HTTP (por defecto 200)
     * @return self            Instancia de Response
     */
    public static function text(string $content, int $statusCode = 200): self {
        $response = new self($content, $statusCode);
        $response->header('Content-Type', 'text/plain; charset=utf-8');
        return $response;
    }

    /**
     * Crea una respuesta HTML
     *
     * @param string $content Contenido HTML
     * @param int $statusCode Código de estado HTTP (por defecto 200)
     * @return self            Instancia de Response
     */
    public static function html(string $content, int $statusCode = 200): self {
        $response = new self($content, $statusCode);
        $response->header('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * Crea una respuesta de error
     *
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP (por defecto 400)
     * @param array $extra Datos adicionales para incluir en la respuesta
     * @return self            Instancia de Response
     */
    public static function error(string $message, int $statusCode = 400, array $extra = []): self {
        return self::json(array_merge([
            'error' => self::getStatusText($statusCode),
            'message' => $message,
        ], $extra), $statusCode);
    }

    /**
     * Establece un header HTTP
     *
     * @param string $name Nombre del header
     * @param string $value Valor del header
     * @return self           Instancia de Response
     */
    public function header(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Establece múltiples headers HTTP
     *
     * @param array $headers Array asociativo de headers [nombre => valor]
     * @return self           Instancia de Response
     */
    public function withHeaders(array $headers): self {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }

    /**
     * Establece el código de estado HTTP
     *
     * @param int $code Código de estado HTTP
     * @return self          Instancia de Response
     */
    public function status(int $code): self {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Obtiene el código de estado HTTP actual
     *
     * @return int          Código de estado HTTP
     */
    public function getStatusCode(): int {
        return $this->statusCode;
    }

    /**
     * Obtener content
     */
    /**
     * Obtiene el contenido de la respuesta
     *
     * @return mixed       Contenido de la respuesta
     */
    public function getContent(): mixed {
        return $this->content;
    }

    /**
     * Obtiene todos los headers HTTP establecidos
     *
     * @return array      Array asociativo de headers
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * Envía la respuesta al cliente
     *
     * Establece el código de estado, los headers y envía el contenido
     */
    public function send(): void {
        // Enviar status code
        http_response_code($this->statusCode);

        // Enviar headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Enviar content
        if (is_array($this->content)) {
            // Si es array y no se estableció Content-Type, enviar como JSON
            if (!isset($this->headers['Content-Type'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($this->content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo $this->content;
            }
        } else {
            echo $this->content;
        }
    }

    /**
     * Convierte la respuesta a string
     *
     * @return string     Representación en string del contenido
     */
    public function __toString(): string {
        if (is_array($this->content)) {
            return json_encode($this->content);
        }

        return (string) $this->content;
    }

    /**
     * Obtiene el texto descriptivo para un código de estado HTTP
     *
     * @param int $code Código de estado HTTP
     * @return string      Texto descriptivo del código de estado
     */
    private static function getStatusText(int $code): string {
        return HttpStatus::fromCode($code)->text();
    }
}
