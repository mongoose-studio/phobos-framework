<?php

namespace PhobosFramework\Core;

use Closure;
use PhobosFramework\Config\Config;
use PhobosFramework\Config\EnvLoader;
use PhobosFramework\Routing\Router;
use PhobosFramework\Http\Request;
use PhobosFramework\Http\Response;
use PhobosFramework\Middleware\Pipeline;
use PhobosFramework\Module\ModuleInterface;
use RuntimeException;
use Throwable;

class Phobos {

    private static ?self $instance = null;
    private string $basePath;
    private Router $router;
    private Container $container;
    private ?Request $request = null;
    private array $providers = [];
    private array $loadedProviders = [];

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
     * Inicializar Phobos (Singleton)
     */
    public static function init(string $basePath): self {
        if (self::$instance === null) {
            self::$instance = new self($basePath);
        }

        return self::$instance;
    }

    /**
     * Obtener instancia actual
     */
    public static function getInstance(): ?self {
        return self::$instance;
    }

    /**
     * Cargar variables de entorno
     */
    public function loadEnvironment(string $envFile = null): self {
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
     * Cargar configuración
     */
    public function loadConfig(string $configPath = null): self {
        Observer::record('phobos.loading_config');

        $configPath = $configPath ?? dirname($this->basePath) . '/config';

        Config::setPath($configPath);

        Observer::record('phobos.config_loaded', [
            'path' => $configPath,
        ]);

        return $this;
    }

    /**
     * Bootstrap de la aplicación con el módulo raíz
     */
    public function bootstrap(string $moduleClass): self {
        Observer::record('phobos.bootstrapping', [
            'module' => $moduleClass,
        ]);

        if (!class_exists($moduleClass)) {
            throw new RuntimeException("Module class {$moduleClass} not found");
        }

        $module = $this->container->make($moduleClass);

        if (!($module instanceof ModuleInterface)) {
            throw new RuntimeException("Module must implement ModuleInterface");
        }

        // Registrar providers del módulo
        $this->registerProviders($module->providers());

        // Boot de providers
        $this->bootProviders();

        // Registrar rutas del módulo
        $module->routes($this->router);

        Observer::record('phobos.bootstrapped', [
            'module' => $moduleClass,
            'routes_count' => count($this->router->getRoutes()),
            'providers_count' => count($this->loadedProviders),
        ]);

        return $this;
    }

    /**
     * Registrar providers
     */
    private function registerProviders(array $providers): void {
        foreach ($providers as $providerClass) {
            if (!class_exists($providerClass)) {
                throw new RuntimeException("Provider class {$providerClass} not found");
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
     * Boot de todos los providers
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
     * Ejecutar la aplicación
     */
    public function run(Request $request = null): Response {
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

            // Obtener middlewares de la ruta
            $middlewares = $match->getMiddlewares();

            Observer::record('phobos.executing_route', [
                'action' => $this->getActionDescription($match->getAction()),
                'middlewares_count' => count($middlewares),
            ]);

            // Ejecutar pipeline de middlewares + controlador
            $response = (new Pipeline($this->request))
                ->through($middlewares)
                ->then(function($request) use ($match) {
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
     * Ejecutar acción del controlador usando Container
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
     * Obtener descripción de la acción
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

    public function getRouter(): Router {
        return $this->router;
    }

    public function getContainer(): Container {
        return $this->container;
    }

    public function getRequest(): ?Request {
        return $this->request;
    }

    public function getBasePath(): string {
        return $this->basePath;
    }
}
