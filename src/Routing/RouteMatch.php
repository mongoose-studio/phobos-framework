<?php

namespace PhobosFramework\Routing;

class RouteMatch {

    public function __construct(
        public readonly Route $route,
        public readonly array $params = [],
    ) {}

    /**
     * Obtener valor de un parámetro
     */
    public function param(string $name, mixed $default = null): mixed {
        return $this->params[$name] ?? $default;
    }

    /**
     * Verificar si existe un parámetro
     */
    public function hasParam(string $name): bool {
        return isset($this->params[$name]);
    }

    /**
     * Obtener todos los parámetros
     */
    public function getParams(): array {
        return $this->params;
    }

    /**
     * Obtener la acción de la ruta
     */
    public function getAction(): mixed {
        return $this->route->getAction();
    }

    /**
     * Obtener los middlewares de la ruta
     */
    public function getMiddlewares(): array {
        return $this->route->getMiddlewares();
    }
}
