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
 * Excepción que se lanza cuando un recurso solicitado no se encuentra disponible.
 *
 * Esta excepción extiende de HttpException y está diseñada para manejar
 * situaciones donde se solicita un recurso que no existe o no está accesible,
 * respondiendo con un código de estado HTTP 404.
 */
class NotFoundException extends HttpException {

    /**
     * Constructor para inicializar la excepción con un mensaje, código y excepción anterior.
     *
     * @param string $message El mensaje de la excepción (por defecto: 'Resource not found').
     * @param Exception|null $previous La excepción anterior utilizada para el encadenamiento de excepciones, si existe.
     * @return void
     */
    public function __construct(string $message = 'Resource not found', ?Exception $previous = null) {
        parent::__construct($message, 404, 'Not Found', [], $previous);
    }
}
