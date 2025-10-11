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

class TooManyRequestsException extends HttpException {

    public function __construct(int $retryAfter = 60, ?Exception $previous = null) {
        parent::__construct(
            'Too many requests',
            429,
            'Too Many Requests',
            ['Retry-After' => (string) $retryAfter],
            $previous
        );
    }
}
