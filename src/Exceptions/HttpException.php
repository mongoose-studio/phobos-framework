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
 *
 * Esta clase maneja las excepciones relacionadas con errores HTTP,
 * permitiendo especificar el código de estado, mensaje de error
 * y cabeceras personalizadas para la respuesta HTTP.
 */
class HttpException extends Exception {

    /**
     * Constructor de la excepción HTTP
     *
     * @param string $message Mensaje descriptivo del error
     * @param int $statusCode Código de estado HTTP (por defecto 500)
     * @param string $error Mensaje de error corto (por defecto 'Internal Server Error')
     * @param array $headers Cabeceras HTTP adicionales
     * @param Exception|null $previous Excepción previa si existe
     */
    public function __construct(
        string $message = '',
        private int $statusCode = 500,
        private string $error = 'Internal Server Error',
        private array $headers = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Obtiene el código de estado HTTP
     *
     * @return int Código de estado HTTP
     */
    public function getStatusCode(): int {
        return $this->statusCode;
    }

    /**
     * Obtiene el mensaje de error corto
     *
     * @return string Mensaje de error
     */
    public function getError(): string {
        return $this->error;
    }

    /**
     * Obtiene las cabeceras HTTP adicionales
     *
     * @return array Cabeceras HTTP
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * Convierte la excepción a un array
     *
     * @return array Datos de la excepción en formato array
     */
    public function toArray(): array {
        return [
            'error' => $this->error,
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
        ];
    }
}
