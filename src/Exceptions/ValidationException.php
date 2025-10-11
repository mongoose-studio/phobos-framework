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

class ValidationException extends HttpException {

    public function __construct(
        private array $errors = [],
        string $message = 'Validation failed',
        ?Exception $previous = null
    ) {
        parent::__construct($message, 422, 'Unprocessable Entity', [], $previous);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function toArray(): array {
        return [
            'error' => $this->getError(),
            'message' => $this->getMessage(),
            'status_code' => $this->getStatusCode(),
            'errors' => $this->errors,
        ];
    }
}
