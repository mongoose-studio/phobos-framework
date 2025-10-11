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
namespace PhobosFramework\Module;

use PhobosFramework\Routing\Router;

/**
 * Interface que deben implementar todos los módulos
 */
interface ModuleInterface {

    /**
     * Registrar rutas del módulo
     */
    public function routes(Router $router): void;

    /**
     * Middlewares globales del módulo
     */
    public function middlewares(): array;

    /**
     * Providers del módulo (opcional)
     */
    public function providers(): array;
}
