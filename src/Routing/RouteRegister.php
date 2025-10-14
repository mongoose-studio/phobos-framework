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

namespace PhobosFramework\Routing;


/**
 * Clase que maneja el registro y configuración de rutas en el framework.
 *
 * Esta clase actúa como un fluent builder para configurar rutas,
 * permitiendo encadenar métodos para establecer middlewares y nombres
 * de manera fluida. Trabaja en conjunto con las clases Route y Router
 * para gestionar el sistema de enrutamiento.
 */
class RouteRegister {

    public function __construct(
        private Route  $route,
        private Router $router,
    ) {
    }

    /**
     * Agrega uno o varios middlewares a la ruta actual.
     *
     * Los middlewares son capas de procesamiento que se ejecutan antes o después
     * del controlador principal. Pueden ser utilizados para autenticación,
     * validación, logging, etc.
     *
     * @param array|string $middleware Middleware único o array de middlewares
     * @return self Retorna la instancia actual para permitir encadenamiento
     */
    public function middleware(array|string $middleware): self {
        $this->route->middleware($middleware);
        return $this;
    }

    /**
     * Asigna un nombre único a la ruta actual.
     *
     * El nombre de la ruta permite referenciarla fácilmente en otras partes
     * de la aplicación, como en la generación de URLs o en la navegación.
     * El nombre debe ser único en toda la aplicación.
     *
     * @param string $name Nombre único para identificar la ruta
     * @return self Retorna la instancia actual para permitir encadenamiento
     */
    public function name(string $name): self {
        $this->route->name($name);
        $this->router->setNamedRoute($name, $this->route);
        return $this;
    }

    /**
     * Obtiene la instancia de la ruta configurada.
     *
     * Retorna el objeto Route actual con toda su configuración,
     * incluyendo middlewares, nombre y otros parámetros establecidos.
     *
     * @return Route La instancia de Route configurada
     */
    public function getRoute(): Route {
        return $this->route;
    }
}
