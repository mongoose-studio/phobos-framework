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
 * Excepción que se lanza cuando un servicio no está disponible temporalmente.
 *
 * Esta excepción corresponde al código de estado HTTP 503 (Service Unavailable)
 * y se utiliza cuando el servidor no puede manejar la solicitud debido a una
 * sobrecarga temporal o mantenimiento del servidor.
 */
class ServiceUnavailableException extends HttpException {

    /**
     * Método constructor para inicializar la excepción con un mensaje específico y una excepción previa opcional.
     *
     * @param string $message El mensaje de error a mostrar (por defecto es 'Service temporarily unavailable').
     * @param Exception|null $previous Una excepción previa opcional utilizada para el encadenamiento de excepciones.
     *
     * @return void
     */
    public function __construct(string $message = 'Service temporarily unavailable', ?Exception $previous = null) {
        parent::__construct($message, 503, 'Service Unavailable', [], $previous);
    }
}
