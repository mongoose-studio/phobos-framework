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

use PhobosFramework\Http\Request;


/**
 * Clase que representa una ruta en el sistema de enrutamiento.
 *
 * Maneja el procesamiento de rutas con parámetros dinámicos, comodines y middlewares.
 * Soporta patrones como :param, * y ** para crear rutas flexibles y reutilizables.
 */
class Route {

    private string $regex;
    private array $paramNames = [];
    private array $middlewares = [];
    private ?string $name = null;

    /**
     * Crea una nueva instancia de ruta.
     *
     * @param array $methods Métodos HTTP permitidos para esta ruta
     * @param string $path Patrón de la ruta (puede incluir :param, * y **)
     * @param mixed $action Acción a ejecutar cuando la ruta coincide
     */
    public function __construct(
        private array  $methods,
        private string $path,
        private mixed  $action,
    ) {
        $this->compilePattern();
    }

    /**
     * Compila el patrón de ruta en una expresión regular.
     *
     * Convierte los diferentes tipos de patrones en expresiones regulares:
     * - :param -> Captura parámetros dinámicos
     * - ** -> Captura todo incluyendo barras (wildcard completo)
     * - * -> Captura un segmento entre barras
     *
     * @return void
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
            $this->paramNames[] = "segment_$wildcardCount";
            $wildcardCount++;
            return '/([^/]+)';
        }, $pattern);

        // Escapar caracteres especiales y anclar
        $pattern = '#^' . $pattern . '$#';

        $this->regex = $pattern;
    }

    /**
     * Verifica si la ruta coincide con una solicitud HTTP.
     *
     * @param Request $request Solicitud HTTP a verificar
     * @return RouteMatch|null Objeto RouteMatch si hay coincidencia, null en caso contrario
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
     * Agrega uno o más middlewares a la ruta.
     *
     * @param array|string $middleware Middleware(s) a agregar
     * @return self
     */
    public function middleware(array|string $middleware): self {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * Asigna un nombre identificador a la ruta.
     *
     * @param string $name Nombre de la ruta
     * @return self
     */
    public function name(string $name): self {
        $this->name = $name;
        return $this;
    }

    /**
     * Getters
     */

    /**
     * Obtiene los métodos HTTP permitidos para esta ruta.
     * @return array
     */
    public function getMethods(): array {
        return $this->methods;
    }

    /**
     * Obtiene el patrón de la ruta.
     * @return string
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * Obtiene la acción asociada a la ruta.
     * @return mixed
     */
    public function getAction(): mixed {
        return $this->action;
    }

    /**
     * Obtiene los middlewares asociados a la ruta.
     * @return array
     */
    public function getMiddlewares(): array {
        return $this->middlewares;
    }

    /**
     * Obtiene el nombre de la ruta.
     * @return string|null
     */
    public function getName(): ?string {
        return $this->name;
    }

    /**
     * Obtiene los nombres de los parámetros dinámicos de la ruta.
     * @return array
     */
    public function getParamNames(): array {
        return $this->paramNames;
    }
}
