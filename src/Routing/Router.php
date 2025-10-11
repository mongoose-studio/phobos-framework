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
use PhobosFramework\Exceptions\NotFoundException;
use PhobosFramework\Core\Observer;
use RuntimeException;

/**
 * # Router (Enrutador)
 *
 * El Router es un componente central que maneja el enrutamiento HTTP en el framework Phobos.
 * Proporciona una API fluida y flexible para definir y gestionar las rutas de la aplicación.
 *
 * ## Características Principales
 *
 * - **Métodos HTTP Soportados**: GET, POST, PUT, DELETE, PATCH, OPTIONS
 * - **Rutas Dinámicas**: Soporte para parámetros en URL (`/usuarios/{id}`)
 * - **Agrupación de Rutas**: Permite organizar rutas con prefijos y middlewares compartidos
 * - **Sistema de Módulos**: Organización modular de rutas relacionadas
 * - **Nombres de Rutas**: Permite referenciar rutas por nombres únicos
 * - **Soportes de Middlewares**: Permite utilizar middlewares con pipelines
 * - **Inyección de Dependencias**: puede inyectar dependencias en controladores
 *
 * ## Ejemplos
 *
 * ```
 * $router->get('/usuarios', [UsuarioController::class, 'index']);
 * ```
 *
 *  ```
 * $router->get('/usuarios/{id}', [UsuarioController::class, 'show']);
 *  ```
 *
 *  ```
 * $router->group(['prefix' => 'admin'], function($router) {
 *     $router->get('dashboard', [AdminController::class, 'index']);
 * });
 * ```
 */
class Router {

    private array $routes = [];
    private array $groupStack = [];
    private array $namedRoutes = [];

    /**
     * Registra una ruta para peticiones HTTP GET
     *
     * Este método permite registrar una ruta que responderá a peticiones GET.
     *
     * @param string $path La ruta URL (ej: `/usuarios` o `/usuarios/{id}`)
     * @param mixed $action El controlador que manejará la petición. Puede ser:
     * - Una función anónima
     * - Un array `[controlador, método]`
     * - Una cadena `"Controlador@método"`
     * @return RouteRegister Un objeto para encadenar configuraciones adicionales
     */
    public function get(string $path, mixed $action): RouteRegister {
        return $this->addRoute(['GET'], $path, $action);
    }

    /**
     * Registra una ruta para peticiones HTTP POST
     *
     * Este método permite registrar una ruta que responderá a peticiones POST,
     * típicamente usadas para crear nuevos recursos.
     *
     * @param string $path La ruta URL (ej: `/usuarios`)
     * @param mixed $action El controlador que manejará la petición
     * @return RouteRegister Un objeto para encadenar configuraciones adicionales
     */
    public function post(string $path, mixed $action): RouteRegister {
        return $this->addRoute(['POST'], $path, $action);
    }

    /**
     * Registra una ruta para peticiones HTTP PUT
     *
     * Este método permite registrar una ruta que responderá a peticiones PUT,
     * típicamente usadas para actualizar recursos existentes.
     *
     * @param string $path La ruta URL (ej: `/usuarios/{id}`)
     * @param mixed $action El controlador que manejará la petición
     * @return RouteRegister Un objeto para encadenar configuraciones adicionales
     */
    public function put(string $path, mixed $action): RouteRegister {
        return $this->addRoute(['PUT'], $path, $action);
    }

    /**
     * Registra una ruta para peticiones HTTP DELETE
     *
     * Este método permite registrar una ruta que responderá a peticiones DELETE,
     * típicamente usadas para eliminar recursos.
     *
     * @param string $path La ruta URL (ej: `/usuarios/{id}`)
     * @param mixed $action El controlador que manejará la petición
     * @return RouteRegister Un objeto para encadenar configuraciones adicionales
     */
    public function delete(string $path, mixed $action): RouteRegister {
        return $this->addRoute(['DELETE'], $path, $action);
    }

    /**
     * Registra una ruta para peticiones HTTP PATCH
     *
     * Este método permite registrar una ruta que responderá a peticiones PATCH,
     * típicamente usadas para actualizaciones parciales de recursos.
     *
     * @param string $path La ruta URL (ej: `/usuarios/{id}`)
     * @param mixed $action El controlador que manejará la petición
     * @return RouteRegister Un objeto para encadenar configuraciones adicionales
     */
    public function patch(string $path, mixed $action): RouteRegister {
        return $this->addRoute(['PATCH'], $path, $action);
    }

    /**
     * Registra una ruta para peticiones HTTP OPTIONS
     *
     * Este método permite registrar una ruta que responderá a peticiones OPTIONS,
     * típicamente usadas para obtener metadatos sobre los recursos.
     *
     * @param string $path La ruta URL
     * @param mixed $action El controlador que manejará la petición
     * @return RouteRegister Un objeto para encadenar configuraciones adicionales
     */
    public function options(string $path, mixed $action): RouteRegister {
        return $this->addRoute(['OPTIONS'], $path, $action);
    }

    /**
     * Registra una ruta que responderá a todos los métodos HTTP
     *
     * Este método registra una ruta que aceptará peticiones con cualquier método HTTP
     * (GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD).
     *
     * @param string $path La ruta URL
     * @param mixed $action El controlador que manejará la petición
     * @return RouteRegister Un objeto para encadenar configuraciones adicionales
     */
    public function all(string $path, mixed $action): RouteRegister {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'], $path, $action);
    }

    /**
     * Registra una ruta que responderá a múltiples métodos HTTP específicos
     *
     * Este método permite registrar una ruta que aceptará peticiones con los métodos
     * HTTP especificados en el array `$methods`.
     *
     * @param array $methods Lista de métodos HTTP permitidos (ej: ['GET', 'POST'])
     * @param string $path La ruta URL
     * @param mixed $action El controlador que manejará la petición
     * @return RouteRegister Un objeto para encadenar configuraciones adicionales
     */
    public function multi(array $methods, string $path, mixed $action): RouteRegister {
        return $this->addRoute(array_map('strtoupper', $methods), $path, $action);
    }

    /**
     * Crea un grupo de rutas con configuración compartida
     *
     * Este método permite agrupar rutas que compartirán atributos comunes como:
     * - Prefijos de URL
     * - Middlewares
     * - Espacios de nombres
     *
     * ### Ejemplo:
     * ```
     * $router->group(['prefix' => 'admin', 'middleware' => 'auth'], function($router) {
     *     $router->get('dashboard', [AdminController::class,'dashboard']);
     * });
     * ```
     *
     * @param array $attributes Atributos compartidos para las rutas del grupo
     * @param callable $callback Función que define las rutas del grupo
     */
    public function group(array $attributes, callable $callback): void {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    /**
     * Registra un módulo de rutas como un grupo independiente
     *
     * Este método permite organizar rutas relacionadas en módulos separados.
     * Cada módulo debe implementar un método `routes()` donde se definirán sus rutas
     * y opcionalmente un método `middlewares()` para definir middlewares compartidos.
     *
     * ### Ejemplo:
     * ```
     * class AdminModule {
     *     public function routes($router) {
     *         $router->get('dashboard', 'AdminController@dashboard');
     *     }
     *
     *     public function middlewares() {
     *         return ['auth', 'admin'];
     *     }
     * }
     *
     * $router->module('/admin', AdminModule::class);
     * ```
     *
     * @param string $prefix Prefijo URL para todas las rutas del módulo
     * @param string $moduleClass Nombre de la clase que define el módulo
     * @throws RuntimeException Si la clase del módulo no existe o no implementa routes()
     */
    public function module(string $prefix, string $moduleClass): void {
        if (!class_exists($moduleClass)) {
            throw new RuntimeException("Module class {$moduleClass} not found");
        }

        $module = new $moduleClass();

        if (!method_exists($module, 'routes')) {
            throw new RuntimeException("Module {$moduleClass} must implement routes() method");
        }

        // Obtener middlewares del módulo si existen
        $middlewares = method_exists($module, 'middlewares') ? $module->middlewares() : [];

        // Crear grupo con el prefijo y middlewares del módulo
        $this->group([
            'prefix' => $prefix,
            'middleware' => $middlewares,
        ], function ($router) use ($module) {
            $module->routes($router);
        });
    }

    /**
     * Agrega una nueva ruta al enrutador
     *
     * Este método privado se encarga de registrar una nueva ruta en el sistema, aplicando:
     * - Configuración de grupos activos
     * - Prefijos de URL
     * - Middlewares heredados
     *
     * ### Ejemplo:
     * ```
     * $router->addRoute(['GET'], '/usuarios', [UserController::class, 'index']);
     * ```
     *
     * @param array $methods Lista de métodos HTTP permitidos para la ruta (ej: ['GET', 'POST'])
     * @param string $path La ruta URL a registrar (ej: `/usuarios` o `/usuarios/{id}`)
     * @param mixed $action El controlador que manejará la petición. Puede ser:
     * - Una función anónima
     * - Un array `[controlador, método]`
     * - Una cadena `"Controlador@método"`
     * @return RouteRegister Un objeto para encadenar configuraciones adicionales
     */
    private function addRoute(array $methods, string $path, mixed $action): RouteRegister {
        // Aplicar configuración de grupos activos
        $groupAttributes = $this->mergeGroupAttributes();

        // Aplicar prefix de grupos
        if (isset($groupAttributes['prefix'])) {
            $path = trim($groupAttributes['prefix'], '/') . '/' . trim($path, '/');
        }

        // Normalizar path
        $path = '/' . trim($path, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Crear ruta
        $route = new Route($methods, $path, $action);

        // Aplicar middlewares de grupos
        if (isset($groupAttributes['middleware'])) {
            $route->middleware($groupAttributes['middleware']);
        }

        $this->routes[] = $route;

        return new RouteRegister($route, $this);
    }

    /**
     * Fusiona los atributos de todos los grupos activos en la pila
     *
     * Este método combina los atributos de configuración de todos los grupos
     * de rutas activos, aplicando las siguientes reglas:
     *
     * - **Middlewares**: Se combinan en un solo array, manteniendo el orden
     * - **Prefijos**: Se concatenan en orden, separados por '/'
     * - **Otros atributos**: Se sobrescriben con el valor más reciente
     *
     * ### Ejemplo:
     * ```
     * $router->group(['prefix' => 'admin'], function($router){
     *     $router->group(['middleware' => ['auth']], function($router){
     *         // Resultado: prefix = 'admin', middleware = ['auth']
     *     });
     * });
     * ```
     *
     * @return array Arreglo con los atributos combinados de todos los grupos activos
     */
    private function mergeGroupAttributes(): array {
        $attributes = [];

        foreach ($this->groupStack as $group) {
            foreach ($group as $key => $value) {
                if ($key === 'middleware') {
                    $attributes[$key] = array_merge(
                        $attributes[$key] ?? [],
                        is_array($value) ? $value : [$value]
                    );
                } elseif ($key === 'prefix') {
                    $attributes[$key] = isset($attributes[$key])
                        ? trim($attributes[$key], '/') . '/' . trim($value, '/')
                        : $value;
                } else {
                    $attributes[$key] = $value;
                }
            }
        }

        return $attributes;
    }

    /**
     * Compara una petición HTTP entrante con una ruta registrada.
     *
     * Este método itera a través de las rutas registradas para encontrar una que coincida
     * con la petición proporcionada. Si encuentra una coincidencia:
     *
     * - Registra la ruta coincidente en el observador
     * - Retorna el resultado con los parámetros extraídos
     *
     * En caso de que ninguna ruta coincida, lanza una excepción `NotFoundException`.
     *
     * ### Ejemplo:
     * ```
     * $match = $router->match($request);
     * // $match->route - La ruta coincidente
     * // $match->params - Los parámetros extraídos
     * ```
     *
     * @param Request $request La petición HTTP a comparar. Contiene información como
     * el método HTTP y la ruta URL solicitada.
     * @return RouteMatch La ruta coincidente junto con los parámetros extraídos.
     * @throws NotFoundException Si no se encuentra ninguna ruta que coincida con la petición.
     */
    public function match(Request $request): RouteMatch {
        Observer::record('router.matching', [
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        foreach ($this->routes as $route) {
            if ($match = $route->matches($request)) {
                Observer::record('router.matched', [
                    'route' => $route->getPath(),
                    'params' => $match->params,
                    'action' => $this->getActionDescription($route->getAction()),
                ]);

                return $match;
            }
        }

        Observer::record('router.not_found', [
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        throw new NotFoundException("Route not found: {$request->method()} {$request->path()}");
    }

    /**
     * Registra un nombre para una ruta específica
     *
     * Este método permite asignar un nombre único a una ruta para poder referenciarla
     * fácilmente más adelante. Los nombres de ruta son útiles para:
     *
     * - Generar URLs dinámicamente
     * - Mantener las URLs centralizadas
     * - Facilitar el mantenimiento de la aplicación
     *
     * ### Ejemplo:
     * ```
     * $router->get('/usuarios/{id}', 'UserController@show')->name('usuarios.ver');
     * ```
     *
     * ### Uso:
     * ```
     * // Generar URL usando el nombre
     * $url = $router->route('usuarios.ver', ['id' => 5]);
     * ```
     *
     * @param string $name El nombre único para la ruta
     * @param Route $route La instancia de ruta a nombrar
     * @throws RuntimeException Si el nombre ya está registrado
     */
    public function setNamedRoute(string $name, Route $route): void {
        if (isset($this->namedRoutes[$name])) {
            throw new RuntimeException("Route name '{$name}' is already registered");
        }

        $this->namedRoutes[$name] = $route;
    }

    /**
     * Genera una URL a partir del nombre de una ruta
     *
     * Este método permite generar URLs dinámicamente usando el nombre de una ruta
     * previamente registrada. Es útil para:
     *
     * - Mantener las URLs consistentes en toda la aplicación
     * - Actualizar URLs en un solo lugar
     * - Manejar parámetros dinámicos en las rutas
     *
     * @param string $name El nombre de la ruta registrada
     * @param array $params Los parámetros para reemplazar en la URL (ej: ['id' => 5])
     * @return string La URL generada
     * @throws RuntimeException Si la ruta no existe o faltan parámetros
     *
     * ### Ejemplos:
     * ```
     * // Definir ruta con nombre
     * $router->get('/usuarios/{id}/editar', [UserController::class, 'edit'])->name('usuarios.editar');
     * ```
     *
     * ```
     * // Generar URL
     * $url = $router->route('usuarios.editar', ['id' => 5]);
     * // Resultado: /usuarios/5/editar
     * ```
     */
    public function route(string $name, array $params = []): string {
        if (!isset($this->namedRoutes[$name])) {
            throw new RuntimeException("Named route '{$name}' not found");
        }

        $route = $this->namedRoutes[$name];
        $path = $route->getPath();

        // Reemplazar parámetros
        foreach ($params as $key => $value) {
            $path = str_replace(":{$key}", $value, $path);
        }

        // Verificar que no queden parámetros sin reemplazar
        if (preg_match('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $path)) {
            throw new RuntimeException("Missing parameters for route '{$name}'");
        }

        return $path;
    }

    /**
     * Obtiene todas las rutas registradas en el enrutador
     *
     * Este método devuelve un array con todas las rutas que han sido registradas
     * en el sistema, incluyendo:
     *
     * - Rutas individuales
     * - Rutas agrupadas
     * - Rutas de módulos
     *
     *  ### Información devuelta:
     *  Cada ruta en el array contiene:
     *  - Métodos HTTP soportados
     *  - Patrón de URL
     *  - Controlador/acción asociada
     *  - Middlewares asignados
     *
     * @return array Lista de todas las rutas registradas como objetos Route
     *
     * ### Ejemplo:
     * ```
     * $rutas = $router->getRoutes();
     * foreach ($rutas as $ruta) {
     *     echo $ruta->getPath();
     * }
     * ```
     */
    public function getRoutes(): array {
        return $this->routes;
    }

    /**
     * Obtiene una descripción legible de una acción de ruta
     *
     * Este método convierte diferentes tipos de acciones de ruta en una representación
     * de texto descriptiva. Procesa los siguientes tipos de acciones:
     *
     * - **Arrays**: Para métodos de clase (`[clase, método]`)
     * - **Strings**: Para acciones en formato string (`"Controller@method"`)
     * - **Closures**: Para funciones anónimas
     *
     *
     * @param mixed $action La acción a describir (array, string o Closure)
     * @return string Representación en texto de la acción. Para métodos de clase
     *                devuelve "Clase::método", para strings devuelve el string original,
     *                y para Closures devuelve "Closure"
     * 
     * ### Ejemplos:
     * ```
     * // Para array con nombre de clase
     * [UserController::class, 'index'] -> "UserController::index"
     * ```
     *
     * ```
     * // Para string
     * "UserController@index" -> "UserController@index"
     * ```
     *
     * ```
     * // Para Closure
     * function() {...} -> "Closure"
     * ``` 
     */
    private function getActionDescription(mixed $action): string {
        if (is_array($action)) {
            return is_string($action[0])
                ? $action[0] . '::' . $action[1]
                : get_class($action[0]) . '::' . $action[1];
        }

        if (is_string($action)) {
            return $action;
        }

        return 'Closure';
    }
}
