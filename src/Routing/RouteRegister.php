<?php

namespace PhobosFramework\Routing;

class RouteRegister {

    public function __construct(
        private Route  $route,
        private Router $router,
    ) {
    }

    /**
     * Agregar middleware(s) a la ruta
     */
    public function middleware(array|string $middleware): self {
        $this->route->middleware($middleware);
        return $this;
    }

    /**
     * Asignar nombre a la ruta
     */
    public function name(string $name): self {
        $this->route->name($name);
        $this->router->setNamedRoute($name, $this->route);
        return $this;
    }

    /**
     * Obtener la ruta configurada
     */
    public function getRoute(): Route {
        return $this->route;
    }
}
