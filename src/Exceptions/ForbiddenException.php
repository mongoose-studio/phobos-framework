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
 * Excepción que representa un error HTTP 403 Forbidden (Prohibido)
 *
 * Esta excepción se lanza cuando el servidor entiende la solicitud pero se niega a autorizarla.
 * A diferencia de 401 Unauthorized (No autorizado), la autenticación no hará ninguna diferencia.
 * El acceso está permanentemente prohibido y no está relacionado con las credenciales proporcionadas.
 */
class ForbiddenException extends HttpException {

    /**
     * Construye una nueva instancia de excepción con el mensaje especificado y una excepción previa opcional.
     *
     * @param string $message El mensaje de la excepción. Por defecto es 'Forbidden'.
     * @param Exception|null $previous La excepción previa utilizada para el encadenamiento de excepciones. Por defecto es null.
     *
     * @return void
     */
    public function __construct(string $message = 'Forbidden', ?Exception $previous = null) {
        parent::__construct($message, 403, 'Forbidden', [], $previous);
    }
}
