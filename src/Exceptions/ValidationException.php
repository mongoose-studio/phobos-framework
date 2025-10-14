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
 * Excepción que se lanza cuando falla la validación de datos.
 * Esta clase extiende HttpException y está diseñada para manejar errores
 * de validación con un código de estado HTTP 422 (Unprocessable Entity).
 */
class ValidationException extends HttpException {

    /**
     * Constructor de la excepción de validación.
     *
     * @param array $errors Lista de errores de validación
     * @param string $message Mensaje de error general
     * @param Exception|null $previous Excepción anterior en la cadena
     */
    public function __construct(
        private array $errors = [],
        string $message = 'Validation failed',
        ?Exception $previous = null
    ) {
        parent::__construct($message, 422, 'Unprocessable Entity', [], $previous);
    }

    /**
     * Obtiene la lista de errores de validación.
     *
     * @return array Lista de errores de validación
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Convierte la excepción a un array con toda la información del error.
     *
     * @return array Información del error en formato array
     */
    public function toArray(): array {
        return [
            'error' => $this->getError(),
            'message' => $this->getMessage(),
            'status_code' => $this->getStatusCode(),
            'errors' => $this->errors,
        ];
    }
}
