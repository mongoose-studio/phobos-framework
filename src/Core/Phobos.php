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

namespace PhobosFramework\Core;

use Closure;
use PhobosFramework\Config\Config;
use PhobosFramework\Config\EnvLoader;
use PhobosFramework\Exceptions\ContainerException;
use PhobosFramework\Routing\Router;
use PhobosFramework\Http\Request;
use PhobosFramework\Http\Response;
use PhobosFramework\Middleware\Pipeline;
use PhobosFramework\Module\ModuleInterface;
use ReflectionException;
use RuntimeException;
use Throwable;

/**
 * Clase principal del framework Phobos
 *
 * Esta clase actúa como el punto de entrada principal del framework y es responsable de:
 * - Inicializar y gestionar el contenedor de dependencias
 * - Cargar la configuración del entorno (.env)
 * - Cargar archivos de configuración
 * - Gestionar el ciclo de vida de los providers
 * - Manejar el enrutamiento de las peticiones HTTP
 * - Ejecutar la aplicación y generar respuestas
 *
 * Se implementa utilizando el patrón Singleton para garantizar una única instancia
 * en toda la aplicación.
 */
class Phobos {

    private static ?self $instance = null;
    private string $basePath;
    private Router $router;
    private Container $container;
    private ?Request $request = null;
    private array $providers = [];
    private array $loadedProviders = [];
    private array $globalMiddlewares = [];
    private array $moduleMiddlewares = [];

    /**
     * Constructor privado de la clase Phobos
     *
     * Inicializa una nueva instancia del framework configurando:
     * - La ruta base de la aplicación
     * - El contenedor de dependencias
     * - El router
     * - Registra las instancias básicas en el contenedor
     *
     * @param string $basePath Ruta base donde se encuentra la aplicación
     */
    private function __construct(string $basePath) {
        $this->basePath = $basePath;
        $this->container = new Container();
        $this->router = new Router();

        // Registrar instancias básicas en el container
        $this->container->instance(Container::class, $this->container);
        $this->container->instance(Router::class, $this->router);
        $this->container->instance(Phobos::class, $this);

        Observer::record('phobos.initialized', [
            'base_path' => $basePath,
        ]);
    }

    /**
     * Inicializa una nueva instancia de Phobos
     *
     * Implementa el patrón Singleton para garantizar una única instancia.
     * Configura el contenedor de dependencias y registra los servicios básicos.
     *
     * @param string $basePath Ruta base de la aplicación
     * @return self Instancia única de Phobos
     */
    public static function init(string $basePath): self {
        if (self::$instance === null) {
            self::$instance = new self($basePath);
        }

        return self::$instance;
    }

    /**
     * Obtiene la instancia actual del framework
     *
     * @return self|null Retorna la instancia única de Phobos o null si no está inicializada
     */
    public static function getInstance(): ?self {
        return self::$instance;
    }

    /**
     * Carga las variables de entorno desde un archivo .env
     *
     * Si no se especifica una ruta, buscará el archivo .env en el directorio padre
     * de la ruta base de la aplicación.
     *
     * @param string|null $envFile Ruta opcional al archivo .env
     * @return self Retorna la instancia actual para encadenamiento
     */
    public function loadEnvironment(?string $envFile = null): self {
        Observer::record('phobos.loading_environment');

        $envFile = $envFile ?? dirname($this->basePath) . '/.env';

        if (!file_exists($envFile)) {
            Observer::record('phobos.environment_not_found', [
                'file' => $envFile,
            ]);
            return $this;
        }

        EnvLoader::load($envFile);

        Observer::record('phobos.environment_loaded', [
            'file' => $envFile,
            'vars_count' => count(EnvLoader::all()),
        ]);

        return $this;
    }

    /**
     * Carga los archivos de configuración de la aplicación
     *
     * Este método:
     * - Define la ruta donde se encuentran los archivos de configuración
     * - Inicializa el sistema de configuración
     * - Permite especificar una ruta personalizada o usar la predeterminada
     *
     * @param string|null $configPath Ruta opcional al directorio de configuración.
     *                               Si no se especifica, usará el directorio /config
     *                               en el directorio padre de la ruta base
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function loadConfig(?string $configPath = null): self {
        Observer::record('phobos.loading_config');

        $configPath = $configPath ?? dirname($this->basePath) . '/config';

        Config::setPath($configPath);

        Observer::record('phobos.config_loaded', [
            'path' => $configPath,
        ]);

        return $this;
    }

    /**
     * Registra middlewares globales que se aplicarán a todas las rutas de la aplicación
     *
     * Este método permite definir middlewares que se ejecutarán para TODAS las rutas,
     * independientemente del módulo al que pertenezcan. Es útil para funcionalidades
     * transversales como CORS, logging, autenticación, etc.
     *
     * Los middlewares globales se ejecutan ANTES que los middlewares del módulo
     * y ANTES que los middlewares específicos de cada ruta.
     *
     * @param array|string $middlewares Middleware único o array de middlewares
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function middleware(array|string $middlewares): self {
        if (is_string($middlewares)) {
            $middlewares = [$middlewares];
        }

        $this->globalMiddlewares = array_merge($this->globalMiddlewares, $middlewares);

        Observer::record('phobos.global_middlewares_registered', [
            'count' => count($middlewares),
            'total' => count($this->globalMiddlewares),
        ]);

        return $this;
    }

    /**
     * Inicializa la aplicación con el módulo raíz especificado
     *
     * Este método:
     * - Registra los providers del módulo
     * - Ejecuta el boot de los providers
     * - Registra los middlewares del módulo
     * - Registra las rutas del módulo
     *
     * @param string $moduleClass Clase del módulo raíz
     * @return self Retorna la instancia actual para encadenamiento
     * @throws RuntimeException|ContainerException Si la clase del módulo no existe o no implementa ModuleInterface
     */
    public function bootstrap(string $moduleClass): self {
        Observer::record('phobos.bootstrapping', [
            'module' => $moduleClass,
        ]);

        if (!class_exists($moduleClass)) {
            throw new RuntimeException("Module class $moduleClass not found");
        }

        $module = $this->container->make($moduleClass);

        if (!($module instanceof ModuleInterface)) {
            throw new RuntimeException("Module must implement ModuleInterface");
        }

        // Registrar providers del módulo
        $this->registerProviders($module->providers());

        // Boot de providers
        $this->bootProviders();

        // Registrar middlewares del módulo
        $moduleMiddlewares = $module->middlewares();
        $this->moduleMiddlewares = array_merge($this->moduleMiddlewares, $moduleMiddlewares);

        Observer::record('phobos.module_middlewares_registered', [
            'count' => count($moduleMiddlewares),
            'total' => count($this->moduleMiddlewares),
        ]);

        // Registrar rutas del módulo
        $module->routes($this->router);

        Observer::record('phobos.bootstrapped', [
            'module' => $moduleClass,
            'routes_count' => count($this->router->getRoutes()),
            'providers_count' => count($this->loadedProviders),
            'module_middlewares_count' => count($this->moduleMiddlewares),
        ]);

        return $this;
    }

    /**
     * Registra los proveedores de servicios de la aplicación
     *
     * Este método:
     * - Verifica que las clases de los providers existan
     * - Instancia cada provider usando el contenedor de dependencias
     * - Ejecuta el método register() de cada provider si existe
     * - Registra eventos de observación para cada provider registrado
     *
     * @param array $providers Lista de clases de providers a registrar
     * @throws RuntimeException|ContainerException Si la clase del provider no existe
     */
    private function registerProviders(array $providers): void {
        foreach ($providers as $providerClass) {
            if (!class_exists($providerClass)) {
                throw new RuntimeException("Provider class $providerClass not found");
            }

            // Instanciar provider usando container
            $provider = $this->container->make($providerClass);

            $this->providers[] = $provider;

            // Ejecutar register
            if (method_exists($provider, 'register')) {
                $provider->register($this->container);
            }

            Observer::record('phobos.provider_registered', [
                'provider' => $providerClass,
            ]);
        }
    }

    /**
     * Inicializa todos los proveedores de servicios registrados
     *
     * Este método:
     * - Ejecuta el método boot() de cada provider si existe
     * - Marca los providers como inicializados
     * - Registra eventos de observación para cada provider iniciado
     * - Se ejecuta después de que todos los providers han sido registrados
     */
    private function bootProviders(): void {
        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot($this->container);
            }

            $this->loadedProviders[] = get_class($provider);

            Observer::record('phobos.provider_booted', [
                'provider' => get_class($provider),
            ]);
        }
    }

    /**
     * Ejecuta la aplicación procesando la petición HTTP
     *
     * Este método:
     * - Captura o utiliza la petición HTTP proporcionada
     * - Encuentra la ruta correspondiente
     * - Ejecuta los middlewares asociados
     * - Ejecuta el controlador o acción correspondiente
     * - Retorna la respuesta generada
     *
     * @param Request|null $request Petición HTTP opcional
     * @return Response Respuesta HTTP generada
     * @throws Throwable Si ocurre algún error durante la ejecución
     */
    public function run(?Request $request = null): Response {
        Observer::record('phobos.running');

        // Capturar request si no se proporciona
        $this->request = $request ?? Request::capture();

        // Registrar request en container
        $this->container->instance(Request::class, $this->request);

        Observer::record('phobos.request_captured', [
            'method' => $this->request->method(),
            'path' => $this->request->path(),
        ]);

        try {
            // Buscar ruta que coincida
            $match = $this->router->match($this->request);

            // Inyectar parámetros de ruta en el request
            $this->request->setParams($match->getParams());

            // Combinar middlewares: globales + módulo + ruta
            // Orden de ejecución: global -> módulo -> ruta -> controlador
            $middlewares = array_merge(
                $this->globalMiddlewares,
                $this->moduleMiddlewares,
                $match->getMiddlewares()
            );

            Observer::record('phobos.executing_route', [
                'action' => $this->getActionDescription($match->getAction()),
                'global_middlewares' => count($this->globalMiddlewares),
                'module_middlewares' => count($this->moduleMiddlewares),
                'route_middlewares' => count($match->getMiddlewares()),
                'total_middlewares' => count($middlewares),
            ]);

            // Ejecutar pipeline de middlewares + controlador
            $response = new Pipeline($this->request)
                ->through($middlewares)
                ->then(function ($request) use ($match) {
                    return $this->executeAction($match->getAction(), $request, $match->getParams());
                });

            Observer::record('phobos.response_ready', [
                'status' => $response->getStatusCode(),
            ]);

            return $response;

        } catch (Throwable $e) {
            Observer::record('phobos.exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Ejecuta una acción del controlador utilizando el contenedor de dependencias
     *
     * Este método:
     * - Maneja diferentes tipos de acciones (Closure, array [Controller, method], string)
     * - Resuelve el controlador desde el contenedor con autowiring si es necesario
     * - Inyecta dependencias en la acción al ejecutarla
     * - Combina los parámetros de la ruta con el objeto Request
     *
     * @param mixed $action La acción a ejecutar (Closure, array o string)
     * @param Request $request Objeto Request actual
     * @param array $params Parámetros extraídos de la ruta
     * @return mixed El resultado de ejecutar la acción
     * @throws ContainerException|ReflectionException Si el tipo de acción no es válido
     */
    private function executeAction(mixed $action, Request $request, array $params): mixed {
        // Si es un Closure
        if ($action instanceof Closure) {
            return $this->container->call($action, array_merge(['request' => $request], $params));
        }

        // Si es array [Controller::class, 'method']
        if (is_array($action)) {
            [$controllerClass, $method] = $action;

            // Resolver controlador desde container (con autowiring)
            $controller = $this->container->make($controllerClass);

            // Llamar método con inyección de dependencias
            return $this->container->call([$controller, $method], array_merge(['request' => $request], $params));
        }

        // Si es string "Controller::method"
        if (is_string($action) && str_contains($action, '::')) {
            [$controllerClass, $method] = explode('::', $action);
            $controller = $this->container->make($controllerClass);
            return $this->container->call([$controller, $method], array_merge(['request' => $request], $params));
        }

        throw new RuntimeException("Invalid action type");
    }

    /**
     * Obtiene una descripción legible de la acción a ejecutar
     *
     * Este método:
     * - Convierte diferentes tipos de acciones en una cadena descriptiva
     * - Maneja acciones de tipo array [Controller, method]
     * - Maneja acciones de tipo string "Controller::method"
     * - Identifica Closures
     *
     * @param mixed $action La acción a describir (puede ser Closure, array o string)
     * @return string Descripción legible de la acción
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

    /**
     * Getters
     */

    /**
     * Obtiene la instancia del Router
     *
     * @return Router Instancia del router que gestiona las rutas de la aplicación
     */
    public function getRouter(): Router {
        return $this->router;
    }

    /**
     * Obtiene la instancia del Contenedor de dependencias
     *
     * @return Container Instancia del contenedor que gestiona las dependencias
     */
    public function getContainer(): Container {
        return $this->container;
    }

    /**
     * Obtiene la petición HTTP actual
     *
     * @return Request|null La petición HTTP actual o null si no se ha capturado
     */
    public function getRequest(): ?Request {
        return $this->request;
    }

    /**
     * Obtiene la ruta base de la aplicación
     *
     * @return string Ruta absoluta al directorio raíz de la aplicación
     */
    public function getBasePath(): string {
        return $this->basePath;
    }
}
