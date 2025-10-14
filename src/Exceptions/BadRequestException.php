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
 * Excepción que se lanza cuando una solicitud HTTP es inválida o mal formada.
 */
class BadRequestException extends HttpException {

    /**
     * Constructor de la excepción de solicitud incorrecta.
     *
     * @param string $message Mensaje de error personalizado (opcional)
     * @param Exception|null $previous Excepción previa que causó este error (opcional)
     */
    public function __construct(string $message = 'Bad request', ?Exception $previous = null) {
        parent::__construct($message, 400, 'Bad Request', [], $previous);
    }
}
