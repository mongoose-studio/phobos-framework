<?php

namespace PhobosFramework\Module;

use PhobosFramework\Routing\Router;
use PhobosFramework\Core\Observer;

/**
 * Cargador de módulos
 */
class ModuleLoader {

    private Router $router;
    private array $loadedModules = [];

    public function __construct(Router $router) {
        $this->router = $router;
    }

    /**
     * Cargar un módulo
     */
    public function load(string $moduleClass, string $prefix = ''): void {
        Observer::record('module.loading', [
            'module' => $moduleClass,
            'prefix' => $prefix,
        ]);

        if (!class_exists($moduleClass)) {
            throw new \RuntimeException("Module class {$moduleClass} not found");
        }

        if (isset($this->loadedModules[$moduleClass])) {
            Observer::record('module.already_loaded', [
                'module' => $moduleClass,
            ]);
            return;
        }

        $module = new $moduleClass();

        if (!($module instanceof ModuleInterface)) {
            throw new \RuntimeException("Module {$moduleClass} must implement ModuleInterface");
        }

        // Registrar módulo como cargado
        $this->loadedModules[$moduleClass] = $module;

        // Obtener middlewares del módulo
        $middlewares = method_exists($module, 'middlewares') ? $module->middlewares() : [];

        // Obtener providers del módulo
        $providers = method_exists($module, 'providers') ? $module->providers() : [];

        // Crear grupo con prefijo y middlewares
        if (!empty($prefix) || !empty($middlewares)) {
            $attributes = [];

            if (!empty($prefix)) {
                $attributes['prefix'] = $prefix;
            }

            if (!empty($middlewares)) {
                $attributes['middleware'] = $middlewares;
            }

            $this->router->group($attributes, function($router) use ($module) {
                $module->routes($router);
            });
        } else {
            $module->routes($this->router);
        }

        Observer::record('module.loaded', [
            'module' => $moduleClass,
            'middlewares_count' => count($middlewares),
            'providers_count' => count($providers),
        ]);
    }

    /**
     * Obtener módulo cargado
     */
    public function getModule(string $moduleClass): ?ModuleInterface {
        return $this->loadedModules[$moduleClass] ?? null;
    }

    /**
     * Obtener todos los módulos cargados
     */
    public function getLoadedModules(): array {
        return $this->loadedModules;
    }

    /**
     * Verificar si un módulo está cargado
     */
    public function isLoaded(string $moduleClass): bool {
        return isset($this->loadedModules[$moduleClass]);
    }
}
