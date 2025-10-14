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
 * Excepción que se lanza cuando una solicitud no está autorizada.
 *
 * Esta excepción se utiliza cuando un usuario intenta acceder a un recurso
 * para el cual no tiene los permisos necesarios. Por defecto, establece
 * el código de estado HTTP 401 y agrega el encabezado 'WWW-Authenticate'.
 */
class UnauthorizedException extends HttpException {

    /**
     * Constructor de la excepción de no autorizado.
     *
     * @param string $message Mensaje de la excepción. Por defecto es 'Unauthorized'.
     * @param Exception|null $previous Excepción previa que causó esta excepción.
     */
    public function __construct(string $message = 'Unauthorized', ?Exception $previous = null) {
        parent::__construct($message, 401, 'Unauthorized', ['WWW-Authenticate' => 'Bearer'], $previous);
    }
}
