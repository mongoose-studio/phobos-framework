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

namespace PhobosFramework\Module;

use PhobosFramework\Routing\Router;
use PhobosFramework\Core\Observer;

/**
 * Cargador de módulos para el framework Phobos
 *
 * Esta clase gestiona la carga y registro de módulos en la aplicación.
 * Maneja el enrutamiento, middlewares y providers de cada módulo.
 */
class ModuleLoader {

    private Router $router;
    private array $loadedModules = [];

    public function __construct(Router $router) {
        $this->router = $router;
    }

    /**
     * Carga un módulo en la aplicación
     *
     * @param string $moduleClass Nombre de la clase del módulo a cargar
     * @param string $prefix Prefijo opcional para las rutas del módulo
     * @throws \RuntimeException Si la clase del módulo no existe o no implementa ModuleInterface
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
     * Obtiene un módulo previamente cargado
     *
     * @param string $moduleClass Nombre de la clase del módulo
     * @return ModuleInterface|null El módulo cargado o null si no existe
     */
    public function getModule(string $moduleClass): ?ModuleInterface {
        return $this->loadedModules[$moduleClass] ?? null;
    }

    /**
     * Obtiene todos los módulos que han sido cargados
     *
     * @return array<string,ModuleInterface> Array asociativo de módulos cargados
     */
    public function getLoadedModules(): array {
        return $this->loadedModules;
    }

    /**
     * Verifica si un módulo específico ya ha sido cargado
     *
     * @param string $moduleClass Nombre de la clase del módulo
     * @return bool True si el módulo está cargado, false en caso contrario
     */
    public function isLoaded(string $moduleClass): bool {
        return isset($this->loadedModules[$moduleClass]);
    }
}
