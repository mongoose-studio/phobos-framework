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

class MethodNotAllowedException extends HttpException {

    public function __construct(array $allowedMethods = [], ?Exception $previous = null) {
        $message = 'Method not allowed';
        $headers = empty($allowedMethods) ? [] : ['Allow' => implode(', ', $allowedMethods)];

        parent::__construct($message, 405, 'Method Not Allowed', $headers, $previous);
    }
}

