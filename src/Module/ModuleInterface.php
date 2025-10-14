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
 * Interface ModuleInterface
 *
 * Esta interfaz define la estructura base que deben implementar todos los módulos del framework.
 * Proporciona los métodos necesarios para la configuración de rutas, middlewares y providers,
 * permitiendo una integración consistente de nuevos módulos en la aplicación.
 *
 * Los módulos son unidades autocontenidas que encapsulan funcionalidad específica,
 * siguiendo el principio de modularidad del framework.
 */
interface ModuleInterface {

    /**
     * Registra las rutas específicas del módulo en el sistema de routing.
     *
     * Este método es llamado durante la inicialización del módulo para definir
     * todas las rutas que el módulo necesita manejar.
     */
    public function routes(Router $router): void;

    /**
     * Define los middlewares globales que se aplicarán a todas las rutas del módulo.
     *
     * Los middlewares permiten procesar las solicitudes antes de que lleguen
     * a los controladores y las respuestas antes de que se envíen al cliente.
     *
     * @return array Lista de middlewares a aplicar
     */
    public function middlewares(): array;

    /**
     * Define los providers que el módulo necesita para su funcionamiento.
     *
     * Los providers son clases que registran servicios en el contenedor de
     * dependencias del framework. Este método es opcional y puede retornar
     * un array vacío si el módulo no requiere providers.
     *
     * @return array Lista de providers del módulo
     */
    public function providers(): array;
}
