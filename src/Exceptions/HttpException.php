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

namespace PhobosFramework\Exceptions;

use Exception;

/**
 * Excepción base para errores HTTP
 */
class HttpException extends Exception {

    public function __construct(
        string $message = '',
        private int $statusCode = 500,
        private string $error = 'Internal Server Error',
        private array $headers = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function getError(): string {
        return $this->error;
    }

    public function getHeaders(): array {
        return $this->headers;
    }

    public function toArray(): array {
        return [
            'error' => $this->error,
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
        ];
    }
}
