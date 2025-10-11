<?php

namespace PhobosFramework\Core;

/**
 * Clase base abstracta para Service Providers
 */
abstract class ServiceProvider implements ServiceProviderInterface {

    /**
     * Indica si el provider debe ser cargado en cada request
     */
    protected bool $defer = false;

    /**
     * Servicios que este provider proporciona
     */
    protected array $provides = [];

    /**
     * Registrar servicios
     */
    abstract public function register(Container $container): void;

    /**
     * Bootstrap de servicios (opcional)
     */
    public function boot(Container $container): void {
        // Override si es necesario
    }

    /**
     * Verificar si el provider es diferido
     */
    public function isDeferred(): bool {
        return $this->defer;
    }

    /**
     * Obtener servicios que proporciona
     */
    public function provides(): array {
        return $this->provides;
    }
}
