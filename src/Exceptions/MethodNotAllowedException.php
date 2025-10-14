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
 * Excepción que se lanza cuando el método HTTP utilizado no está permitido.
 *
 * Esta excepción se genera cuando una solicitud HTTP se realiza con un método
 * que no está permitido para el recurso solicitado. Por ejemplo, cuando se intenta
 * realizar una petición POST a un endpoint que solo acepta GET.
 */
class MethodNotAllowedException extends HttpException {

    /**
     * Constructor de la excepción MethodNotAllowed.
     *
     * @param array $allowedMethods Lista de métodos HTTP permitidos para este recurso
     * @param Exception|null $previous Excepción anterior en la cadena (para encadenamiento de excepciones)
     */
    public function __construct(array $allowedMethods = [], ?Exception $previous = null) {
        $message = 'Method not allowed';
        $headers = empty($allowedMethods) ? [] : ['Allow' => implode(', ', $allowedMethods)];

        parent::__construct($message, 405, 'Method Not Allowed', $headers, $previous);
    }
}

