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

namespace PhobosFramework\Middleware;

use PhobosFramework\Http\Request;
use PhobosFramework\Http\Response;
use Closure;

/**
 * Interface que deben implementar todos los middlewares
 */
interface MiddlewareInterface {

    /**
     * Manejar el request
     *
     * @param Request $request El request HTTP
     * @param Closure $next Siguiente middleware/controlador en la cadena
     * @return Response La respuesta HTTP
     */
    public function handle(Request $request, Closure $next): Response;
}
