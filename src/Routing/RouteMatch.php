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
 * Clase que representa una coincidencia exitosa de una ruta.
 *
 * Contiene la ruta coincidente y los parámetros extraídos de la URL.
 * Esta clase se utiliza para almacenar y acceder a la información
 * resultante después de que una ruta ha coincidido con una solicitud HTTP.
 */
class RouteMatch {

    public function __construct(
        public readonly Route $route,
        public readonly array $params = [],
    ) {
    }

    /**
     * Obtiene el valor de un parámetro específico de la ruta.
     *
     * @param string $name Nombre del parámetro a obtener
     * @param mixed $default Valor por defecto si el parámetro no existe
     * @return mixed El valor del parámetro o el valor por defecto
     */
    public function param(string $name, mixed $default = null): mixed {
        return $this->params[$name] ?? $default;
    }

    /**
     * Verifica si existe un parámetro específico en la ruta.
     *
     * @param string $name Nombre del parámetro a verificar
     * @return bool True si el parámetro existe, false en caso contrario
     */
    public function hasParam(string $name): bool {
        return isset($this->params[$name]);
    }

    /**
     * Obtiene todos los parámetros extraídos de la ruta.
     *
     * @return array Array asociativo con todos los parámetros de la ruta
     */
    public function getParams(): array {
        return $this->params;
    }

    /**
     * Obtiene la acción (controlador o callback) asociada a la ruta.
     *
     * @return mixed La acción que se ejecutará para esta ruta
     */
    public function getAction(): mixed {
        return $this->route->getAction();
    }

    /**
     * Obtiene la lista de middlewares asociados a la ruta.
     *
     * @return array Lista de middlewares que se ejecutarán para esta ruta
     */
    public function getMiddlewares(): array {
        return $this->route->getMiddlewares();
    }

    /**
     * Devuelve la ruta asociada.
     *
     * @return Route La información de la ruta asociada
     */
    public function getRoute(): Route {
        return $this->route;
    }
}
