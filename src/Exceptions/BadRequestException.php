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

class BadRequestException extends HttpException {

    public function __construct(string $message = 'Bad request', ?Exception $previous = null) {
        parent::__construct($message, 400, 'Bad Request', [], $previous);
    }
}
