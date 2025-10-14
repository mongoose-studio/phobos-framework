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
 * Excepción que se lanza cuando ocurre un error en el contenedor de dependencias.
 *
 * Esta excepción se utiliza específicamente para manejar errores relacionados con
 * la inyección de dependencias y la gestión del contenedor IoC (Inversión de Control).
 */
class ContainerException extends Exception {
}
