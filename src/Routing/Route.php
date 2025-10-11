<?php

namespace PhobosFramework\Routing;

use PhobosFramework\Http\Request;

class Route {

    private string $regex;
    private array $paramNames = [];
    private array $middlewares = [];
    private ?string $name = null;

    public function __construct(
        private array  $methods,
        private string $path,
        private mixed  $action,
    ) {
        $this->compilePattern();
    }

    /**
     * Compilar pattern de ruta a regex
     */
    private function compilePattern(): void {
        $pattern = $this->path;

        // Convertir :param a regex con nombres de captura
        $pattern = preg_replace_callback('/:([a-zA-Z_][a-zA-Z0-9_]*)/', function ($matches) {
            $this->paramNames[] = $matches[1];
            return '([^/]+)'; // Captura cualquier cosa excepto /
        }, $pattern);

        // Convertir ** a regex (captura todo incluyendo /)
        if (str_contains($pattern, '/**')) {
            $pattern = str_replace('/**', '(?:/(.*))?', $pattern);
            $this->paramNames[] = 'wildcard';
        }

        // Convertir * a regex (captura un segmento)
        $wildcardCount = 0;
        $pattern = preg_replace_callback('/\/\*/', function () use (&$wildcardCount) {
            $this->paramNames[] = "segment_{$wildcardCount}";
            $wildcardCount++;
            return '/([^/]+)';
        }, $pattern);

        // Escapar caracteres especiales y anclar
        $pattern = '#^' . $pattern . '$#';

        $this->regex = $pattern;
    }

    /**
     * Verificar si esta ruta coincide con el request
     */
    public function matches(Request $request): ?RouteMatch {
        // Verificar método HTTP
        if (!in_array($request->method(), $this->methods)) {
            return null;
        }

        // Verificar path
        $path = $request->path();

        if (!preg_match($this->regex, $path, $matches)) {
            return null;
        }

        // Extraer parámetros
        array_shift($matches); // Quitar el match completo

        // Combinar nombres de parámetros con valores capturados
        $params = [];
        foreach ($this->paramNames as $index => $name) {
            if (isset($matches[$index])) {
                $params[$name] = $matches[$index];
            }
        }

        return new RouteMatch($this, $params);
    }

    /**
     * Agregar middleware(s) a la ruta
     */
    public function middleware(array|string $middleware): self {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * Establecer nombre de la ruta
     */
    public function name(string $name): self {
        $this->name = $name;
        return $this;
    }

    /**
     * Getters
     */

    public function getMethods(): array {
        return $this->methods;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getAction(): mixed {
        return $this->action;
    }

    public function getMiddlewares(): array {
        return $this->middlewares;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getParamNames(): array {
        return $this->paramNames;
    }
}
