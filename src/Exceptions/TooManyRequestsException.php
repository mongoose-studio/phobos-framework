<?php

/**
 * # Phobos Framework
 *
 * Para la información completa acerca del copyright y la licencia,
 * por favor vea el archivo LICENSE que va distribuido con el código fuente.
 *
 * @package     PhobosFramework
 * @author      Marcel Rojas <marcelrojas16@gmail.com>
 * @copyright   Copyright (c) 2012-2025, Marcel Rojas <marcelrojas16@gmail.com>
 * @license     MIT License
 */

namespace PhobosFramework\Exceptions;

use Exception;


/**
 * Excepción que se lanza cuando se realizan demasiadas solicitudes a un recurso.
 *
 * Esta excepción corresponde al código de estado HTTP 429 "Too Many Requests".
 * Se utiliza cuando el usuario ha enviado demasiadas solicitudes en un período
 * de tiempo determinado ("limitación de velocidad"). La excepción incluye un
 * encabezado Retry-After que indica cuánto tiempo debe esperar el cliente
 * antes de realizar una nueva solicitud.
 *
 * @throws HttpException Con código de estado 429
 */
class TooManyRequestsException extends HttpException {

    /**
     * Método constructor para inicializar la excepción con parámetros específicos.
     *
     * @param int $retryAfter Número de segundos que indica cuánto tiempo debe esperar el cliente antes de reintentar. Por defecto es 60.
     * @param Exception|null $previous La excepción anterior utilizada para el encadenamiento de excepciones. Opcional.
     *
     * @return void
     */
    public function __construct(int $retryAfter = 60, ?Exception $previous = null) {
        parent::__construct(
            'Too many requests',
            429,
            'Too Many Requests',
            ['Retry-After' => (string)$retryAfter],
            $previous
        );
    }
}
