<?php

namespace PhobosFramework\Http;

class Response {

    private array $headers = [];
    private int $statusCode;
    private mixed $content;

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
     * Crear respuesta JSON
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
     * Crear respuesta vacía
     */
    public static function empty(int $statusCode = 204): self {
        return new self('', $statusCode);
    }

    /**
     * Crear respuesta de texto
     */
    public static function text(string $content, int $statusCode = 200): self {
        $response = new self($content, $statusCode);
        $response->header('Content-Type', 'text/plain; charset=utf-8');
        return $response;
    }

    /**
     * Crear respuesta HTML
     */
    public static function html(string $content, int $statusCode = 200): self {
        $response = new self($content, $statusCode);
        $response->header('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * Crear respuesta de error
     */
    public static function error(string $message, int $statusCode = 400, array $extra = []): self {
        return self::json(array_merge([
            'error' => self::getStatusText($statusCode),
            'message' => $message,
        ], $extra), $statusCode);
    }

    /**
     * Establecer header
     */
    public function header(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Establecer múltiples headers
     */
    public function withHeaders(array $headers): self {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }

    /**
     * Establecer status code
     */
    public function status(int $code): self {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Obtener status code
     */
    public function getStatusCode(): int {
        return $this->statusCode;
    }

    /**
     * Obtener content
     */
    public function getContent(): mixed {
        return $this->content;
    }

    /**
     * Obtener headers
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * Enviar la respuesta
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
     * Convertir a string
     */
    public function __toString(): string {
        if (is_array($this->content)) {
            return json_encode($this->content);
        }

        return (string) $this->content;
    }

    /**
     * Obtener texto del status code usando el Enum
     */
    private static function getStatusText(int $code): string {
        return HttpStatus::fromCode($code)->text();
    }
}
